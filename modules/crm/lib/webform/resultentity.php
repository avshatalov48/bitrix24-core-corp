<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm;

use Bitrix\Crm\Ads\Pixel\ConversionEventTriggers\WebFormTrigger;
use Bitrix\Crm;
use Bitrix\Crm\Automation;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\EntityManageFacility;
use Bitrix\Crm\Integration\UserConsent as CrmIntegrationUserConsent;
use Bitrix\Crm\Integrity\ActualEntitySelector;
use Bitrix\Crm\Merger\EntityMerger;
use Bitrix\Crm\Order\TradingPlatform;
use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\WebForm\Internals\ResultEntityTable;
use Bitrix\Crm\Activity\Provider;
use Bitrix\Crm\Activity\BindingSelector;
use Bitrix\Crm\ClientInfo;
use Bitrix\Crm\Integration\Channel\WebFormTracker;
use Bitrix\Crm\Order\OrderCreator;
use Bitrix\Main\UserConsent\Consent;
use Bitrix\Crm\Settings\LayoutSettings;
use Bitrix\Crm\Tracking;
use Bitrix\Sale;
use Bitrix\Sale\Delivery\Services\EmptyDeliveryService;
use Bitrix\Sale\Helpers\Order\Builder\BuildingException;
use Bitrix\Sale\Shipment;

Loc::loadMessages(__FILE__);

class ResultEntity
{
	CONST DUPLICATE_CONTROL_MODE_REPLACE = 'REPLACE';
	CONST DUPLICATE_CONTROL_MODE_MERGE = 'MERGE';
	CONST DUPLICATE_CONTROL_MODE_NONE = '';
	CONST INVOICE_PAYER_COMPANY = 'COMPANY';
	CONST INVOICE_PAYER_CONTACT = 'CONTACT';

	protected $formId = null;
	protected $formData = array();
	protected $duplicateMode = null;
	protected $resultId = null;

	protected $entityMap = null;
	protected $scheme = null;
	protected $fields = array();
	protected $productRows = array();
	protected $currencyId = null;
	protected $presetFields = array();
	protected $placeholders = array();
	protected $commonFields = array();
	protected $commonData = array();
	protected $assignedById = null;
	protected $activityFields = array();
	protected $invoiceSettings = array();
	protected $isDealDuplicateControlEnabled = false;
	protected $isDynamicDuplicateControlEnabled = false;

	protected $isCallback = false;
	protected $callbackPhone = null;

	protected $activityId = null;
	protected $contactId = null;
	protected $companyId = null;
	protected $dealId = null;
	protected $leadId = null;
	protected $quoteId = null;
	protected $invoiceId = null;
	protected $orderId = null;
	protected $paymentId = null;
	protected $dynamicTypeId = null;
	protected $dynamicId = null;

	protected $resultEntityPack = array();

	/** @var ActualEntitySelector  */
	protected $selector = null;
	/** @var Tracking\Trace $trace  */
	protected $trace;
	protected $traceId;
	protected $entities = [];
	protected $agreements = [];

	public static function getDuplicateModes()
	{
		return array_keys(self::getDuplicateModeList());
	}

	public static function getDuplicateModeList()
	{
		return array(
			self::DUPLICATE_CONTROL_MODE_MERGE => Loc::getMessage('CRM_WEBFORM_RESULT_ENTITY_DC_MERGE'),
			self::DUPLICATE_CONTROL_MODE_REPLACE => Loc::getMessage('CRM_WEBFORM_RESULT_ENTITY_DC_REPLACE'),
			self::DUPLICATE_CONTROL_MODE_NONE => Loc::getMessage('CRM_WEBFORM_RESULT_ENTITY_DC_NONE'),
		);
	}

	protected function findDuplicateEntityId($entityTypeName, $fields)
	{
		if(!$this->duplicateMode || $this->duplicateMode == self::DUPLICATE_CONTROL_MODE_NONE)
		{
			return null;
		}

		$entity = Entity::getMap($entityTypeName);
		if(!$entity || empty($entity['DUPLICATE_CHECK']))
		{
			return null;
		}

		$mergerOptions = ['ENABLE_UPLOAD' => true, 'ENABLE_UPLOAD_CHECK' => false];

		switch ($entityTypeName)
		{
			case \CCrmOwnerType::CompanyName:
				$rowId = $this->selector->getCompanyId();
				break;

			case \CCrmOwnerType::ContactName:
				$rowId = $this->selector->getContactId();
				break;

			case \CCrmOwnerType::DealName:
				$rowId = $this->isDealDuplicateControlEnabled
					? $this->selector->getDealId()
					: null
				;
				break;

			case \CCrmOwnerType::LeadName:
				$rowId = $this->selector->getReturnCustomerLeadId();
				if ($rowId)
				{
					$facility = new EntityManageFacility($this->selector);
					$facility->setUpdateClientMode(
						$this->duplicateMode == self::DUPLICATE_CONTROL_MODE_REPLACE
							?
							EntityManageFacility::UPDATE_MODE_REPLACE
							:
							EntityManageFacility::UPDATE_MODE_MERGE
					);
					$facility->updateClientFields($fields);
					return $rowId;
				}
				else
				{
					$rowId = $this->selector->getLeadId();
				}
				break;

			default:
				$entityTypeId = \CCrmOwnerType::resolveID($entityTypeName);
				if (!\CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId))
				{
					return null;
				}

				$rowId = $this->isDynamicDuplicateControlEnabled
					? $this->selector->getDynamicId()
					: null
				;
				if (!$rowId)
				{
					return null;
				}

				$dynamicFactory = Crm\Service\Container::getInstance()->getFactory($entityTypeId);
				$dynamicItem = $dynamicFactory->getItem($rowId);
				if (!$dynamicItem)
				{
					return null;
				}

				switch($this->duplicateMode)
				{
					case self::DUPLICATE_CONTROL_MODE_MERGE:
						$entityFields = $dynamicItem->getData();
						foreach ($entityFields as $key => $value)
						{
							if ($value === [] || $value === null || $value === '' || $value === false)
							{
								unset($entityFields[$key]);
							}
						}
						break;

					case self::DUPLICATE_CONTROL_MODE_REPLACE:
						$entityFields = [];
						break;

					default:
						return $rowId;
				}

				$merger = new class($entityTypeId, $rowId, false) extends EntityMerger {
					/** @var \Bitrix\Crm\Service\Factory */
					public $dynamicFactory;
					protected function getEntityFieldsInfo()
					{
						return $this->dynamicFactory->getFieldsInfo();
					}

					protected function getEntityUserFieldsInfo()
					{
						return $this->dynamicFactory->getUserFieldsInfo();
					}

					protected function getEntityFields($entityID, $roleID){}
					protected function getEntityResponsibleID($entityID, $roleID){}
					protected function checkEntityReadPermission($entityID,$userPermissions){}
					protected function checkEntityUpdatePermission($entityID,$userPermissions){}
					protected function checkEntityDeletePermission($entityID,$userPermissions){}
					protected function updateEntity($entityID,array &$fields,$roleID,array $options = array()){}
					protected function deleteEntity($entityID,$roleID,array $options = array()){}
				};
				$merger->dynamicFactory = $dynamicFactory;
				$merger->mergeFields($fields, $entityFields, false, $mergerOptions);

				$dynamicItem->setFromCompatibleData($entityFields);
				$dynamicOperation = $dynamicFactory->getUpdateOperation(
					$dynamicItem,
					(new Crm\Service\Context())->setUserId($this->assignedById ?: 1)
				);
				$dynamicResult = $dynamicOperation
					->disableCheckAccess()
					->disableCheckFields()
					->launch();
				$dynamicResult->isSuccess();

				return $rowId;
		}

		if (!$rowId)
		{
			return null;
		}

		/** @var  \CCrmContact $entityObject */
		$entityObject = new $entity['CLASS_NAME'](false);

		$entityMultiFields = array();
		$hasMultiFields = !empty($entity['HAS_MULTI_FIELDS']);
		if ($hasMultiFields)
		{
			$multiFields = \CCrmFieldMulti::GetEntityFields($entityTypeName, $rowId, null);
			foreach($multiFields as $multiField)
			{
				$entityMultiFields[$multiField['TYPE_ID']][$multiField['ID']] = [
						'VALUE' => $multiField['VALUE'],
						'VALUE_TYPE' => $multiField['VALUE_TYPE'],
				];
			}
			unset($multiFields);
		}

		/** @var  $merger \Bitrix\Crm\Merger\EntityMerger */
		$mergerClass = $entity['DUPLICATE_CHECK']['MERGER_CLASS_NAME'];
		switch($this->duplicateMode)
		{
			case self::DUPLICATE_CONTROL_MODE_MERGE:
				$entityFieldsDb = $entityObject->GetListEx(
					array(),
					array('ID' => $rowId, 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('*', 'UF_*')
				);

				if (!$entityFields = $entityFieldsDb->Fetch())
				{
					return null;
				}

				if ($hasMultiFields)
				{
					$entityFields['FM'] = $entityMultiFields;
				}
				foreach ($entityFields as $key => $value)
				{
					if ($value === [] || $value === null || $value === '' || $value === false)
					{
						unset($entityFields[$key]);
					}
				}
				$merger = new $mergerClass(0, false);
				$merger->mergeFields($fields, $entityFields, false, $mergerOptions);

				$entityObject->Update($rowId, $entityFields);
				break;

			case self::DUPLICATE_CONTROL_MODE_REPLACE:
				$entityFields = [];
				if ($hasMultiFields)
				{
					$entityFields['FM'] = $entityMultiFields;
				}

				if (in_array($entityTypeName, [\CCrmOwnerType::DealName, \CCrmOwnerType::ContactName, \CCrmOwnerType::CompanyName]))
				{
					$fieldName = $entityTypeName === \CCrmOwnerType::ContactName ? 'NAME' : 'TITLE';
					$filledValue = $this->fields[$entityTypeName][$fieldName] ?? null;
					$mergedValue = $fields[$fieldName] ?? null;
					if ($mergedValue && !$filledValue)
					{
						unset($fields[$fieldName]);
					}
				}

				$merger = new $mergerClass(0, false);
				$merger->mergeFields($fields, $entityFields, false, $mergerOptions);
				$entityObject->Update($rowId, $entityFields);
				break;
		}

		return $rowId;
	}

	/*
	 * Return ID of invoice
	 * @return int|null
	 */
	public function getInvoiceId()
	{
		return $this->invoiceId;
	}

	/*
	 * Return ID of order
	 * @return int|null
	 */
	public function getOrderId()
	{
		return $this->orderId;
	}

	/*
	 * Return ID of order
	 * @return int|null
	 */
	public function getPaymentId()
	{
		return $this->paymentId;
	}

	/*
	 * Return ID of activity
	 * @return int|null
	 */
	public function getActivityId()
	{
		return $this->activityId;
	}

	protected function replaceDefaultFieldValue($template)
	{
		static $replace = null;
		if(!$replace)
		{
			$replaceList = array(
				'RESULT_ID' => $this->resultId,
				'FORM_NAME' => $this->formData['NAME']
			);

			$replace['from'] = array();
			$replace['to'] = array();
			foreach($replaceList as $replaceFrom => $replaceTo)
			{
				$replace['from'][] = '#' . $replaceFrom . '#';
				$replace['to'][] = $replaceTo;
			}
		}

		return str_replace($replace['from'], $replace['to'], $template);
	}

	protected function getTemplateFieldValue($entityName, $fieldName)
	{
		if(!$this->entityMap)
		{
			$this->entityMap = Entity::getMap();
		}

		if(!isset($this->entityMap[$entityName]))
		{
			return null;
		}

		if(!isset($this->entityMap[$entityName]['FIELD_AUTO_FILL_TEMPLATE']))
		{
			return null;
		}

		if(!isset($this->entityMap[$entityName]['FIELD_AUTO_FILL_TEMPLATE'][$fieldName]))
		{
			return null;
		}

		$fieldProperties = $this->entityMap[$entityName]['FIELD_AUTO_FILL_TEMPLATE'][$fieldName];
		return $this->replaceDefaultFieldValue($fieldProperties['TEMPLATE']);
	}

	protected function getEntityFields(string $entityName, ?bool $onlyDefaultsMode = null): array
	{
		$filledFields = $this->fields[$entityName] ?? [];
		if ($onlyDefaultsMode === false)
		{
			return $filledFields;
		}

		if(!$this->entityMap)
		{
			$this->entityMap = Entity::getMap();
		}

		$fields = [];
		foreach(($this->entityMap[$entityName]['FIELD_AUTO_FILL_TEMPLATE'] ?? []) as $fieldName => $fieldProperties)
		{
			if(isset($fields[$fieldName]) && $fields[$fieldName])
			{
				continue;
			}

			$fields[$fieldName] =  $this->replaceDefaultFieldValue($fieldProperties['TEMPLATE']);
		}

		if ($onlyDefaultsMode)
		{
			return $fields;
		}

		return $filledFields + $fields;
	}

	public function setProductRows($productList)
	{
		foreach($productList as $product)
		{
			$price = $product['PRICE'];
			$taxRate = isset($product['VAT_RATE'])
				? round(doubleval($product['VAT_RATE']) * 100, 2)
				: null
			;
			if ($taxRate)
			{
				$isTaxIncluded = isset($product['VAT_INCLUDED']) && $product['VAT_INCLUDED'] === 'Y';
				$price = $isTaxIncluded
					? $product['PRICE']
					: \CCrmProductRow::CalculateInclusivePrice($product['PRICE'], $taxRate)
				;
			}

			$this->productRows[] = [
				'PRODUCT_ID' => (int) $product['ID'],
				'PRODUCT_NAME' => $product['NAME'],
				'PRICE' => $price,
				'DISCOUNT_SUM' => $product['DISCOUNT'],
				'QUANTITY' => ($product['QUANTITY'] ?? 1) ?: 1,
				'TAX_INCLUDED' => $product['VAT_INCLUDED'] ?? null,
				'TAX_RATE' => $taxRate,
			];
		}
	}

	public function setPresetFields($fields = array())
	{
		$this->presetFields = array();
		foreach ($fields as $presetField)
		{
			$fieldCode = $presetField['ENTITY_NAME'] . '_'. $presetField['FIELD_NAME'];
			$field = EntityFieldProvider::getField($fieldCode);
			if (!$field)
			{
				continue;
			}

			$fieldValues = is_array($presetField['VALUE']) ? $presetField['VALUE'] : array($presetField['VALUE']);
			$presetField['VALUE'] = $field['multiple'] ? $fieldValues : $fieldValues[0];

			$this->presetFields[] = $presetField;
		}
	}

	public function setActivityFields($activityFields = array())
	{
		$this->activityFields = $activityFields;
	}

	public function setInvoiceSettings($invoiceSettings = array())
	{
		$this->invoiceSettings = $invoiceSettings;
	}

	protected function getProductRowsSum()
	{
		$result = 0;
		foreach($this->productRows as $productRow)
		{
			$result += $productRow['QUANTITY'] * ($productRow['PRICE'] - $productRow['DISCOUNT']);
		}

		return $result;
	}

	protected function getProductRows($withProductId = true)
	{
		if($withProductId)
		{
			return $this->productRows;
		}
		else
		{
			$result = array();
			foreach($this->productRows as $productRow)
			{
				$productRow['PRODUCT_ID'] = 0;
				$result[] = $productRow;
			}

			return $result;
		}
	}

	protected function fillFieldsByPresetFields($entityName, $entityFields)
	{
		if(!$this->presetFields)
		{
			return $entityFields;
		}

		$fields = [];
		foreach ($this->fields as $fieldEntityName => $fieldValues)
		{
			foreach ($fieldValues as $fieldName => $fieldValue)
			{
				if ($fieldName === 'FM' && is_array($fieldValue))
				{
					foreach ($fieldValue as $fieldMultiKey => $fieldMultiValue)
					{
						$fieldMultiValue = $fieldMultiValue['n0']['VALUE'] ?? '';
						$key = "{$fieldEntityName}_{$fieldMultiKey}";
						$key = mb_strtolower($key);
						$fields[$key] = $fieldMultiValue;
					}
				}
				elseif (!is_array($fieldValue))
				{
					$key = "{$fieldEntityName}_{$fieldName}";
					$key = mb_strtolower($key);
					$fields[$key] = $fieldValue;
				}
			}
		}

		$placeholders = $this->placeholders;
		$placeholders['crm_form_id'] = $this->formId;
		$placeholders['crm_form_name'] = $this->formData['NAME'];
		$placeholders['crm_result_id'] = $this->resultId;
		$placeholders += $fields;

		foreach($this->presetFields as $presetField)
		{
			if($presetField['ENTITY_NAME'] != $entityName)
			{
				continue;
			}

			$value = $presetField['VALUE'];
			$fromList = $toList = array();
			foreach ($placeholders as $key => $val)
			{
				$fromList[] = '%' . $key . '%';
				$toList[] = $val;
			}
			$value = str_replace($fromList,	$toList, $value);
			$value = preg_replace("/%([a-z0-9_]+?)%/i", '', $value);
			$entityFields[$presetField['FIELD_NAME']] = $value;
		}

		return $entityFields;
	}

	protected function addByEntityName($entityName, $params = array())
	{
		$entityFields = $this->getEntityFields($entityName, false);
		if($params['FIELDS'])
		{
			$entityFields = $params['FIELDS'] + $entityFields;
		}

		if(count($entityFields) == 0)
		{
			return null;
		}

		if(!$this->entityMap[$entityName])
		{
			return null;
		}

		$isEntityInvoice = $entityName == \CCrmOwnerType::InvoiceName;
		$isEntityLead = $entityName == \CCrmOwnerType::LeadName;
		$isEntityContact = $entityName === \CCrmOwnerType::ContactName;
		$isEntityCompany = $entityName === \CCrmOwnerType::CompanyName;
		$isEntityDynamic = !empty($params['DYNAMIC_ENTITY']);
		$entityTypeId = \CCrmOwnerType::resolveID($entityName);

		if(!$isEntityInvoice)
		{
			$entityFields = $entityFields + $this->getCommonFields();
		}

		$entityFields = $this->fillFieldsByPresetFields($entityName, $entityFields);
		[$requisiteFields, $entityFields] = Requisite::separateFieldValues($entityTypeId, $entityFields);

		if($this->assignedById)
		{
			if ($isEntityInvoice)
			{
				$entityFields['RESPONSIBLE_ID'] = $this->assignedById;
			}
			else
			{
				$entityFields['ASSIGNED_BY_ID'] = $this->assignedById;
			}
		}

		$productRows = $this->getProductRows();
		$isNeedAddProducts = ($params['SET_PRODUCTS'] && count($productRows) > 0);
		$isLeadOrQuoteOrDeal = in_array($entityName, array(\CCrmOwnerType::LeadName, \CCrmOwnerType::DealName, \CCrmOwnerType::QuoteName));
		$entity = $this->entityMap[$entityName];
		/** @var \CCrmLead $entityClassName */
		$entityClassName = $entity['CLASS_NAME'] ?? null;

		$isEntityAdded = false;
		$id = $this->findDuplicateEntityId($entityName, $entityFields);
		$facility = new EntityManageFacility($this->selector);
		if(!$id)
		{
			if($isNeedAddProducts && ($isLeadOrQuoteOrDeal || $isEntityInvoice))
			{
				$entityFields["CURRENCY_ID"] = $this->currencyId;
				$entityFields["OPPORTUNITY"] = $this->getProductRowsSum();
				$entityFields["IS_MANUAL_OPPORTUNITY"] = $entityFields["OPPORTUNITY"] > 0 ? 'Y' : 'N';
			}

			if($isNeedAddProducts && $isEntityInvoice)
			{
				$entityFields["PRODUCT_ROWS"] = $this->getProductRows(false);
			}

			if($this->isCallback && isset($entityFields['SOURCE_ID']) && $entityFields['SOURCE_ID'] == 'WEBFORM')
			{
				$entityFields['SOURCE_ID'] = 'CALLBACK';
			}

			$entityFields += $this->getEntityFields($entityName, true);

			$addOptions = [
				'DISABLE_USER_FIELD_CHECK' => true,
				'CURRENT_USER' => $this->assignedById
			];
			if($isEntityDynamic)
			{
				$entityFields['WEBFORM_ID'] = $this->formId;
				$dynamicFactory = Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::resolveID($entityName));
				$dynamicItem = $dynamicFactory->createItem();
				$dynamicItem->setFromCompatibleData($entityFields);
				if (empty($entityFields['STAGE_ID']) && !empty($entityFields['CATEGORY_ID']))
				{
					$dynamicStageId = $dynamicFactory->getStages($entityFields['CATEGORY_ID'])->getStatusIdList()[0] ?? null;
					if ($dynamicStageId)
					{
						$dynamicItem->setStageId($dynamicStageId);
					}
				}

				if ($isNeedAddProducts)
				{
					$dynamicItem->setProductRowsFromArrays($productRows);
				}
				$dynamicOperation = $dynamicFactory->getAddOperation(
					$dynamicItem,
					(new Crm\Service\Context())->setUserId($this->assignedById ?: 1)
				);
				$dynamicResult = $dynamicOperation
					->disableCheckAccess()
					->disableCheckFields()
					->launch();
				$id = $dynamicResult->isSuccess() ? $dynamicItem->getId() : null;
			}
			elseif($isEntityInvoice)
			{
				/** @var \CCrmInvoice $entityInstance */
				$entityInstance = new $entityClassName(false);
				$recalculateOptions = false;
				$entityFields = $this->fixInvoiceFieldsAnonymousUser($entityFields);
				$id = $entityInstance->Add($entityFields, $recalculateOptions, SITE_ID, $addOptions);
			}
			else
			{
				/** @var \CCrmLead $entityInstance */
				$entityInstance = new $entityClassName(false);
				$entityFields['WEBFORM_ID'] = $this->formId;
				if($isEntityLead)
				{
					$facility->setRegisterMode(EntityManageFacility::REGISTER_MODE_ALWAYS_ADD);
					$id = $facility->addLead($entityFields, true, $addOptions);
				}
				else
				{
					$id = $entityInstance->add($entityFields, true, $addOptions);
				}

			}
			$isEntityAdded = true;
		}

		if($id)
		{
			if($isNeedAddProducts && $isLeadOrQuoteOrDeal && $entityClassName)
			{
				$entityClassName::SaveProductRows($id, $productRows, false);
			}

			$resultEntityInfo = [
				'RESULT_ID' => $this->resultId,
				'ENTITY_NAME' => $entityName,
				'ITEM_ID' => $id,
				'IS_DUPLICATE' => !$isEntityAdded,
				'IS_AUTOMATION_RUN' => false,
			];

			if ($isEntityLead && $isEntityAdded)
			{
				if ($facility->convertLead($id))
				{
					$resultEntityInfo['IS_AUTOMATION_RUN'] = true;
				}
				foreach ($facility->getRegisteredEntities() as $complex)
				{
					$this->resultEntityPack[] = [
						'RESULT_ID' => $this->resultId,
						'ENTITY_NAME' => \CCrmOwnerType::resolveName($complex->getTypeId()),
						'ITEM_ID' => $complex->getId(),
						'IS_DUPLICATE' => false,
						'IS_AUTOMATION_RUN' => false,
					];
				}
			}
			/**add delivery address for company/contact/lead */
			if ($isEntityCompany || $isEntityContact)
			{
				$addressFields = [
					'ADDRESS_1' => $entityFields["DELIVERY_ADDRESS"] ?? null,
				];

				if (!EntityAddress::isEmpty($addressFields))
				{
					EntityAddress::register(
						\CCrmOwnerType::ResolveID($entityName), $id, EntityAddressType::Delivery, $addressFields
					);
				}
			}

			if ($requisiteFields)
			{
				$form = \Bitrix\Crm\Integration\Sign\Form::create();
				$form->load($this->formId);
				$requisitePresetId = $form->getRequisitePresetId() ?? null;
				Requisite::instance()->fill($entityTypeId, $id, $requisiteFields, $requisitePresetId);
			}

			$this->resultEntityPack[] = $resultEntityInfo;

			if (
				!$isEntityAdded
				&& in_array($this->duplicateMode, [self::DUPLICATE_CONTROL_MODE_REPLACE, self::DUPLICATE_CONTROL_MODE_MERGE])
				&& in_array($entityName, [\CCrmOwnerType::LeadName, \CCrmOwnerType::DealName])
			)
			{
				$previousFields = $entityClassName::GetByID($id, false);
				$starter = new Automation\Starter(\CCrmOwnerType::ResolveID($entityName), $id);
				$starter->runOnUpdate($entityFields, $previousFields ?: []);
			}
		}

		return $id;
	}

	protected function addDeal($dealParams = array())
	{
		$this->addClient();

		$params = array();
		$params['FIELDS'] = array();
		if($this->companyId || $this->contactId)
		{
			if($this->companyId)
			{
				$params['FIELDS']['COMPANY_ID'] = $this->companyId;
			}

			if($this->contactId)
			{
				$params['FIELDS']['CONTACT_ID'] = $this->contactId;
			}
		}

		if(is_array($this->formData['FORM_SETTINGS']) && isset($this->formData['FORM_SETTINGS']['DEAL_CATEGORY']))
		{
			$params['FIELDS']['CATEGORY_ID'] = $this->formData['FORM_SETTINGS']['DEAL_CATEGORY'];
		}

		$this->isDealDuplicateControlEnabled = ($this->formData['FORM_SETTINGS']['DEAL_DC_ENABLED'] ?? 'N') === 'Y';

		$params['SET_PRODUCTS'] = true;
		$this->dealId = $this->addByEntityName(\CCrmOwnerType::DealName, $params);

		if ($this->dealId)
		{
			WebFormTracker::getInstance()->registerDeal($this->dealId, array('ORIGIN_ID' => $this->formId));
		}

		if($dealParams['ADD_INVOICE'])
		{
			$this->addInvoice();
		}
	}

	protected function addLead($leadParams = array())
	{
		$this->addClient();

		$params = [
			'SET_PRODUCTS' => true
		];

		if ($this->companyId)
		{
			$params['FIELDS']['COMPANY_ID'] = $this->companyId;
		}

		if ($this->contactId)
		{
			$params['FIELDS']['CONTACT_ID'] = $this->contactId;
		}

		if ($this->leadId = $this->addByEntityName(\CCrmOwnerType::LeadName, $params))
		{
			WebFormTracker::getInstance()->registerLead($this->leadId, array('ORIGIN_ID' => $this->formId));
		}

		if ($leadParams['ADD_INVOICE'])
		{
			$this->addInvoice();
		}
	}

	protected function addCompany()
	{
		$this->companyId = $this->addByEntityName(\CCrmOwnerType::CompanyName);
	}

	protected function addContact()
	{
		$params = array();
		if($this->companyId)
		{
			$params['FIELDS'] = array('COMPANY_ID' => $this->companyId);
		}

		$this->contactId = $this->addByEntityName(\CCrmOwnerType::ContactName, $params);
	}

	protected function addClient($clientParams = array())
	{
		$isSchemeHasOrder = $this->isSchemeHasOrder();
		$isAddCompany = isset($this->fields[\CCrmOwnerType::CompanyName]);
		$isAddContact =
			isset($this->fields[\CCrmOwnerType::ContactName])
			|| (!$isAddCompany && $isSchemeHasOrder)
		;

		if($isAddCompany)
		{
			$this->addCompany();
		}

		if($isAddContact)
		{
			$this->addContact();
		}

		if($clientParams['ADD_INVOICE'])
		{
			$this->addInvoice();
		}
	}

	protected function addQuote($quoteParams = array())
	{
		$this->addClient();

		$params = [];
		if($this->companyId || $this->contactId)
		{
			$params['FIELDS'] = array();
			if($this->companyId)
			{
				$params['FIELDS']['COMPANY_ID'] = $this->companyId;
			}

			if($this->contactId)
			{
				$params['FIELDS']['CONTACT_ID'] = $this->contactId;
			}
		}

		$params['SET_PRODUCTS'] = true;
		$this->quoteId = $this->addByEntityName(\CCrmOwnerType::QuoteName, $params);

		if($quoteParams['ADD_INVOICE'])
		{
			$this->addInvoice();
		}
	}

	protected function addDynamic($options = [])
	{
		$this->addClient();

		$params = array(
			'FIELDS' => [
				'SOURCE_ID' => 'WEBFORM',
			]
		);
		if($this->companyId || $this->contactId)
		{
			if($this->companyId)
			{
				$params['FIELDS']['COMPANY_ID'] = $this->companyId;
			}

			if($this->contactId)
			{
				$params['FIELDS']['CONTACT_ID'] = $this->contactId;
			}
		}

		$this->isDynamicDuplicateControlEnabled = ($this->formData['FORM_SETTINGS']['DYNAMIC_DC_ENABLED'] ?? 'N') === 'Y';

		$params['SET_PRODUCTS'] = true;
		$params['DYNAMIC_ENTITY'] = true;
		$entityTypeId = (int)($options['DYNAMIC_TYPE_ID'] ?? 0);
		$categoryId = (int)($this->formData['FORM_SETTINGS']['DYNAMIC_CATEGORY'] ?? 0);
		if (!$entityTypeId)
		{
			return;
		}
		if ($categoryId)
		{
			$params['FIELDS']['CATEGORY_ID'] = $categoryId;
		}

		$this->dynamicTypeId = $entityTypeId;
		$this->dynamicId = $this->addByEntityName(\CCrmOwnerType::resolveName($entityTypeId), $params);

		if($options['ADD_INVOICE'])
		{
			$this->addInvoice();
		}
	}

	private function isSchemeHasOrder() : bool
	{
		return isset($this->scheme) && Entity::isSchemeSupportEntity($this->scheme,\CCrmOwnerType::Invoice);
	}

	protected function getInvoiceSettingsPayer()
	{
		$payer = null;
		if($this->invoiceSettings && $this->invoiceSettings['PAYER'])
		{
			$payer = $this->invoiceSettings['PAYER'];
		}

		return $payer;
	}

	protected function isInvoiceSettingsPayerCompany()
	{
		return $this->getInvoiceSettingsPayer() == self::INVOICE_PAYER_COMPANY;
	}

	protected function isInvoiceSettingsPayerContact()
	{
		return $this->getInvoiceSettingsPayer() == self::INVOICE_PAYER_CONTACT;
	}

	protected function addOrder()
	{
		$formData = $this->getDataForOrderBuilder();
		if (!$formData)
		{
			return;
		}

		// if created automatically - fill it!
		if ($this->dealId)
		{
			$orderId = OrderCreator::getCreatedOrderId((int)$this->dealId);
			if ($orderId)
			{
				$order = Crm\Order\Order::load($orderId);
				if ($order)
				{
					$this->fillOrderPaymentsAndDeliveries($order, $formData);
					return;
				}
			}
		}

		$builder = Crm\Order\Builder\Factory::createDefaultBuilder();

		try
		{
			$builder->build($formData);
		}
		catch (BuildingException $exception)
		{
			return;
		}

		/** @var Crm\Order\Order $order */
		$order = $builder->getOrder();
		if (!$order)
		{
			return;
		}

		$payment = $this->findNewPayment($order);

		$r = $order->save();
		if (!$r->isSuccess())
		{
			return;
		}

		$this->orderId = $order->getId();
		if ($payment)
		{
			$this->paymentId = $payment->getId();
		}

		$this->resultEntityPack[] = [
			'RESULT_ID' => $this->resultId,
			'ENTITY_NAME' => \CCrmOwnerType::OrderName,
			'ITEM_ID' => $this->orderId,
			'IS_DUPLICATE' => false,
			'IS_AUTOMATION_RUN' => false,
		];
	}

	/**
	 * Default order pay system.
	 *
	 * If an order is specified, the pay system is selected taking into account the order restrictions.
	 *
	 * @param Crm\Order\Order|null $order
	 *
	 * @return Sale\PaySystem\Service|null
	 */
	private function getDefaultOrderPaySystem(?Crm\Order\Order $order = null): ?Sale\PaySystem\Service
	{
		if (isset($order))
		{
			$availablePaySystems = Sale\PaySystem\Manager::getListWithRestrictionsByOrder($order);
		}
		else
		{
			$availablePaySystems =
				Sale\PaySystem\Manager::getList([
					'filter' => [
						'=ACTIVE' => 'Y',
					],
				])
					->fetchAll()
			;
		}

		if (empty($availablePaySystems))
		{
			return null;
		}

		foreach ($availablePaySystems as $paySystemInfo)
		{
			if ($paySystemInfo['ACTION_FILE'] === 'cash')
			{
				return new Sale\PaySystem\Service($paySystemInfo);
			}
		}

		$paySystemInfo = reset($availablePaySystems);

		return new Sale\PaySystem\Service($paySystemInfo);
	}

	/**
	 * Create if not exists payments and deliveries.
	 *
	 * @param Crm\Order\Order $order
	 * @param array $formData
	 *
	 * @return void
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\NotImplementedException
	 * @throws Main\NotSupportedException
	 * @throws Main\ObjectNotFoundException
	 * @throws Main\SystemException
	 */
	private function fillOrderPaymentsAndDeliveries(Crm\Order\Order $order, array $formData): void
	{
		$paymentCollection = $order->getPaymentCollection();
		$payment = $paymentCollection->current();
		if (!$payment)
		{
			$orderPaySystem = $this->getDefaultOrderPaySystem($order);
			if ($orderPaySystem)
			{
				$payment = $paymentCollection->createItem($orderPaySystem);
				$payment->setField('SUM', $order->getPrice());
			}
		}

		$shipmentCollection = $order->getShipmentCollection();
		$shipment = $shipmentCollection->getNotSystemItems()->current();
		if (!$shipment)
		{
			$delivery = Sale\Delivery\Services\Manager::getObjectById(
				$formData['SHIPMENT']['DELIVERY_ID'] ?? EmptyDeliveryService::getEmptyDeliveryServiceId()
			);

			/**
			 * @var Shipment $shipment
			 */
			$shipment = $shipmentCollection->createItem($delivery);
			$shipment->setField('ALLOW_DELIVERY', 'Y');

			$shipmentItems = $shipment->getShipmentItemCollection();
			foreach ($order->getBasket() as $basketItem)
			{
				$shipmentItem = $shipmentItems->createItem($basketItem);
				$shipmentItem->setQuantity($basketItem->getQuantity());
			}
		}

		if ($order->isChanged())
		{
			$result = $order->save();
			if (!$result->isSuccess())
			{
				return;
			}
		}

		$this->orderId = $order->getId();

		if ($payment)
		{
			$this->paymentId = $payment->getId();
		}

		$this->resultEntityPack[] = [
			'RESULT_ID' => $this->resultId,
			'ENTITY_NAME' => \CCrmOwnerType::OrderName,
			'ITEM_ID' => $this->orderId,
			'IS_DUPLICATE' => false,
			'IS_AUTOMATION_RUN' => false,
		];
	}

	/**
	 * @param Crm\Order\Order $order
	 * @return Crm\Order\Payment|null
	 */
	private function findNewPayment(Crm\Order\Order $order): ?Crm\Order\Payment
	{
		foreach ($order->getPaymentCollection() as $payment)
		{
			if ($payment->getId() === 0)
			{
				return $payment;
			}
		}

		return null;
	}

	protected function getDataForOrderBuilder()
	{
		if ($this->dealId)
		{
			$ownerTypeId = \CCrmOwnerType::Deal;
			$ownerId = $this->dealId;
		}
		elseif ($this->leadId)
		{
			$ownerTypeId = \CCrmOwnerType::Lead;
			$ownerId = $this->leadId;
		}
		elseif ($this->contactId)
		{
			$ownerTypeId = \CCrmOwnerType::Contact;
			$ownerId = $this->contactId;
		}
		elseif ($this->companyId)
		{
			$ownerTypeId = \CCrmOwnerType::Company;
			$ownerId = $this->companyId;
		}
		else
		{
			return [];
		}

		$formData = [
			'PRODUCT' => [],
			'RESPONSIBLE_ID' => $this->assignedById,
			'SHIPMENT' => [
				[
					'DELIVERY_ID' => EmptyDeliveryService::getEmptyDeliveryServiceId(),
					'ALLOW_DELIVERY' => 'Y',
				]
			]
		];

		$paySystem = $this->getDefaultOrderPaySystem();
		if ($paySystem)
		{
			$formData['PAYMENT'] = [
				[
					'PAY_SYSTEM_ID' => $paySystem->getField('ID'),
					'SUM' => $this->getProductRowsSum(),
				],
			];
		}

		$client = ClientInfo::createFromOwner($ownerTypeId, $ownerId)->toArray();

		if(
			!empty($client['OWNER_ID'])
			&& !empty($client['OWNER_TYPE_ID'])
		)
		{
			$formData['OWNER_ID'] = $client['OWNER_ID'];
			$formData['OWNER_TYPE_ID'] = $client['OWNER_TYPE_ID'];
			unset($client['OWNER_ID'], $client['OWNER_TYPE_ID']);
		}

		$formData['CLIENT'] = $client;

		$code = TradingPlatform\WebForm::getCodeByFormId($this->formId);
		$platform = TradingPlatform\WebForm::getInstanceByCode($code);
		if ($platform->isInstalled())
		{
			$formData['TRADING_PLATFORM'] = $platform->getId();
		}

		$formData['PRODUCT'] = Catalog::create()->setItems($this->getProductRows())->getOrderProducts();


		return $formData;
	}

	/**
	 * Get basket item by ID.
	 * @param $productId
	 * @param array $options
	 * @return array
	 * @throws Main\SystemException
	 * @deprecated
	 */
	public function getBasketItemById($productId, array $options = [])
	{
		$measure = null;
		$product = null;
		if ($productId)
		{
			$product = \CCrmProduct::getByID($productId);
			if (!$product)
			{
				$productId = 0;
			}
			$measure = \Bitrix\Crm\Measure::getProductMeasures($productId);
			if ($measure)
			{
				$measure = $measure[$productId][0];
			}
		}

		if (!$productId)
		{
			$measure = \Bitrix\Crm\Measure::getDefaultMeasure();
		}

		if (!$measure)
		{
			$measure = \Bitrix\Crm\Measure::getDefaultMeasure();
		}

		return [
			'PRODUCT_ID' => $productId,
			'OFFER_ID' => $productId,
			'SORT' => $product['SORT'] ?? 100,
			'MODULE' => $productId ? 'catalog' : '',
			'QUANTITY' => $options['quantity'] ?? 1,
			'CUSTOM_PRICE' => 'Y',
			'NAME' => $options['name'] ?? $product['NAME'],
			'BASE_PRICE' => $options['price'] ?? $product['PRICE'],
			'PRICE' => $options['price'] ?? $product['PRICE'],
			'MEASURE_NAME' => $measure['SYMBOL'],
			'MEASURE_CODE' => $measure['CODE']
		];
	}

	protected function addInvoice($params = [])
	{
		if (Manager::isOrdersAvailable())
		{
			$this->addOrder();
			return;
		}

		if(!isset($params['FIELDS']))
		{
			$params['FIELDS'] = [];
		}

		$personTypes = \CCrmPaySystem::getPersonTypeIDs();
		$currentPersonTypeId = (int)$personTypes['CONTACT'];

		$isPersonTypeSet = false;
		if($this->companyId && $this->isInvoiceSettingsPayerCompany())
		{
			$isPersonTypeSet = true;
			$currentPersonTypeId = (int)$personTypes['COMPANY'];
			$params['FIELDS']['PERSON_TYPE_ID'] = $currentPersonTypeId;
			$params['FIELDS']['UF_COMPANY_ID'] = $this->companyId;
			$params['FIELDS']['INVOICE_PROPERTIES'] = array('COMPANY' => '-');
		}

		if($this->contactId && $this->isInvoiceSettingsPayerContact())
		{
			$isPersonTypeSet = true;
			$params['FIELDS']['UF_CONTACT_ID'] = $this->contactId;
			if(!$params['FIELDS']['PERSON_TYPE_ID'])
			{
				$currentPersonTypeId = (int)$personTypes['CONTACT'];
				$params['FIELDS']['PERSON_TYPE_ID'] = $currentPersonTypeId;
			}
			$params['FIELDS']['INVOICE_PROPERTIES'] = array('CONTACT' => '-');
		}

		if($this->dealId)
		{
			$params['FIELDS']['UF_DEAL_ID'] = $this->dealId;
		}
		if($this->quoteId)
		{
			$params['FIELDS']['UF_QUOTE_ID'] = $this->quoteId;
		}

		if(!$isPersonTypeSet)
		{
			return;
		}


		$billList = \CCrmPaySystem::GetPaySystemsListItems($currentPersonTypeId);
		if ($billList)
		{
			foreach ($billList as $billId => $billName)
			{
				$params['FIELDS']['PAY_SYSTEM_ID'] = $billId;
				break;
			}
		}
		else
		{
			if (Loader::includeModule('sale'))
			{
				$dbRes = \Bitrix\Sale\PaySystem\Manager::getList([
					'filter' => [
						'=PERSON_TYPE_ID' => $currentPersonTypeId,
						'=ENTITY_REGISTRY_TYPE' => REGISTRY_TYPE_CRM_INVOICE,
						'%ACTION_FILE' => ['bill', 'invoicedocument']
					]
				]);

				while ($data = $dbRes->fetch())
				{
					$params['FIELDS']['PAY_SYSTEM_ID'] = $data['ID'];
					break;
				}
			}
		}

		$params['SET_PRODUCTS'] = true;
		$this->invoiceId = $this->addByEntityName(\CCrmOwnerType::InvoiceName, $params);
	}

	protected function addConsent()
	{
		if ($this->formData['USE_LICENCE'] != 'Y')
		{
			return;
		}

		foreach ($this->agreements as $agreementId)
		{
			Consent::addByContext(
				$agreementId,
				CrmIntegrationUserConsent::PROVIDER_CODE,
				$this->activityId,
				[
					'URL' => $this->trace->getUrl()
				]
			);
		}
	}

	/**
	 * Get trace.
	 * @return Tracking\Trace
	 */
	public function getTrace()
	{
		return $this->performTrace();
	}

	protected function performTrace()
	{
		if ($this->trace)
		{
			return $this->trace;
		}

		$traceId = isset($this->commonData['TRACE_ID']) ? $this->commonData['TRACE_ID'] : null;
		if ($traceId)
		{
			$this->trace = new Tracking\Trace(); // TODO: restore data
		}
		else
		{
			$trace = isset($this->commonData['TRACE']) ? $this->commonData['TRACE'] : null;
			if (!($trace instanceof Tracking\Trace))
			{
				$trace = Tracking\Trace::create($trace);
			}
			if ($trace->getUrl())
			{
				$this->placeholders['from_url'] = $trace->getUrl();
				$uri = new Main\Web\Uri($trace->getUrl());
				$this->placeholders['from_domain'] = $uri->getHost();
				$uriParameters = [];
				parse_str($uri->getQuery(), $uriParameters);
				$this->placeholders += $uriParameters;
			}
			elseif(!empty($this->placeholders['from_url']))
			{
				$trace->setUrl($this->placeholders['from_url']);
			}
			if (empty($trace->getUtm()) && !empty($this->commonFields))
			{
				foreach ($this->commonFields as $commonFieldKey => $commonFieldVal)
				{
					$trace->addUtm($commonFieldKey, $commonFieldVal);
				}
			}
			if ($this->isCallback)
			{
				$trace->addChannel(new Tracking\Channel\Callback($this->formId));
			}
			else
			{
				$trace->addChannel(new Tracking\Channel\Form($this->formId));
			}
			$traceId = $trace->save();
			$this->trace = $trace;
		}

		$this->traceId = $traceId;

		return $this->trace;
	}

	protected function fillTrace()
	{
		$entities = [];
		foreach($this->resultEntityPack as $entity)
		{
			if ($entity['IS_DUPLICATE'])
			{
				continue;
			}

			$entities[] = [
				'ENTITY_TYPE_ID' => \CCrmOwnerType::ResolveID($entity['ENTITY_NAME']),
				'ENTITY_ID' => $entity['ITEM_ID']
			];
		}

		if (empty($entities))
		{
			return;
		}

		foreach ($entities as $entity)
		{
			Tracking\Trace::appendEntity(
				$this->traceId,
				$entity['ENTITY_TYPE_ID'],
				$entity['ENTITY_ID']
			);
		}
	}

	protected function addActivity()
	{
		// prepare bindings
		$bindings = BindingSelector::findBindings($this->selector);

		$mainEntityTypeId = Entity::getSchemes($this->scheme)['MAIN_ENTITY'];

		foreach($this->resultEntityPack as $entity)
		{
			$entityTypeId = \CCrmOwnerType::ResolveID($entity['ENTITY_NAME']);
			$bindings[] = [
				'OWNER_ID' => $entity['ITEM_ID'],
				'OWNER_TYPE_ID' => $entityTypeId,
			];

			if ($mainEntityTypeId === $entityTypeId)
			{
				$mainEntityName = \CCrmOwnerType::GetDescription($entityTypeId);
				$mainEntityLink = \Bitrix\Crm\Service\Container::getInstance()
					->getRouter()
					->getItemDetailUrl($entityTypeId, $entity['ITEM_ID'])
					->getLocator();
			}
		}
		if (
			!isset($mainEntityName)
			&& isset($this->resultEntityPack[0])
		)
		{
			$entityTypeId = \CCrmOwnerType::ResolveID($this->resultEntityPack[0]['ENTITY_NAME']);
			$mainEntityName = \CCrmOwnerType::GetDescription($entityTypeId);
			$mainEntityLink = \Bitrix\Crm\Service\Container::getInstance()
				->getRouter()
				->getItemDetailUrl($entityTypeId, $this->resultEntityPack[0]['ITEM_ID'])
				->getLocator();
		}


		$bindings = BindingSelector::sortBindings($bindings);
		$bindings = $this->removeIgnoredActivityBindings($bindings);
		if (empty($bindings))
		{
			return;
		}

		// add activity
		$activityFields = array(
			'TYPE_ID' =>  \CCrmActivityType::Provider,
			'PROVIDER_ID' => Provider\WebForm::PROVIDER_ID,
			'PROVIDER_TYPE_ID' => $this->formId,
			'DIRECTION' => \CCrmActivityDirection::Incoming,
			'ASSOCIATED_ENTITY_ID' => $this->resultId,
			'START_TIME' => new Main\Type\DateTime(),
			'COMPLETED' => LayoutSettings::getCurrent()->isSliderEnabled() ? 'Y' : 'N',
			'PRIORITY' => \CCrmActivityPriority::Medium,
			'DESCRIPTION' => '',
			'DESCRIPTION_TYPE' => \CCrmContentType::PlainText,
			'LOCATION' => '',
			'NOTIFY_TYPE' => \CCrmActivityNotifyType::None,
			'SETTINGS' => array(),
			'AUTHOR_ID' => $this->assignedById,
			'RESPONSIBLE_ID' => $this->assignedById,
			'ORIGIN_ID' => '',
			'BINDINGS' => $bindings,
			'PROVIDER_PARAMS' => array(
				'FIELDS' => $this->activityFields,
				'FORM' => array(
					'IS_USED_USER_CONSENT' => $this->formData['USE_LICENCE'] == 'Y',
					'AGREEMENTS' => $this->agreements,
					'IP' => Context::getCurrent()->getRequest()->getRemoteAddress(),
					'LINK' => Script::getUrlContext($this->formData)
				),
				'VISITED_PAGES' => array_map(
					function ($page)
					{
						return [
							'HREF' => $page['URL'],
							'DATE' => ($page['DATE_INSERT'] instanceof Main\Type\DateTime)
								? $page['DATE_INSERT']->getTimestamp()
								: null,
							'TITLE' => $page['TITLE']
						];
					},
					$this->trace->getPages()
				)
			)
		);

		if ($this->isCallback)
		{
			$activityFields['SUBJECT'] = Loc::getMessage(
				'CRM_WEBFORM_RESULT_ENTITY_NOTIFY_SUBJECT_CALL',
				[
					"%phone%" => htmlspecialcharsbx($this->callbackPhone ? $this->callbackPhone : $this->formData['NAME']),
				]
			);
		}
		else
		{
			$activityFields['SUBJECT'] = Loc::getMessage(
				'CRM_WEBFORM_RESULT_ENTITY_NOTIFY_SUBJECT',
				[
					"%title%" => htmlspecialcharsbx($this->formData['NAME']),
				]
			);
		}

		$activityFields = $this->fillFieldsByPresetFields(\CCrmOwnerType::ActivityName, $activityFields);

		$productRowsSum = $this->getProductRowsSum();
		if($productRowsSum > 0)
		{
			$activityFields['RESULT_SUM'] = $productRowsSum;
			$activityFields['RESULT_CURRENCY_ID'] = $this->currencyId;
		}

		$communications = array();
		if($this->contactId)
		{
			$communicationFields = $this->getEntityFields(\CCrmOwnerType::ContactName);
			$communications[] = array(
				'TYPE' => '',
				'VALUE' => $communicationFields['NAME'],
				'ENTITY_ID' => $this->contactId,
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Contact
			);
		}
		if($this->companyId)
		{
			$communicationFields = $this->getEntityFields(\CCrmOwnerType::CompanyName);
			$communications[] = array(
				'TYPE' => '',
				'VALUE' => $communicationFields['TITLE'],
				'ENTITY_ID' => $this->companyId,
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Company
			);
		}
		if($this->leadId)
		{
			$communicationFields = $this->getEntityFields(\CCrmOwnerType::LeadName);
			$communications[] = array(
				'TYPE' => '',
				'VALUE' => $communicationFields['TITLE'],
				'ENTITY_ID' => $this->leadId,
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Lead
			);
		}

		$id = \CCrmActivity::Add(
			$activityFields, false, true,
			array('REGISTER_SONET_EVENT' => true)
		);
		if($id > 0)
		{
			$this->activityId = $id;
			if(count($communications) > 0)
			{
				\CCrmActivity::SaveCommunications($this->activityId, $communications, $activityFields, true, false);
			}

/*			if ($this->isCallback)
			{
				CallBackWebFormTracker::getInstance()->registerActivity($this->activityId);
			}
			else
*/			{
				WebFormTracker::getInstance()->registerActivity($this->activityId, array('ORIGIN_ID' => $this->formId));
			}

			if(Loader::includeModule('im'))
			{
				$url = "/crm/activity/?open_view=#log_id#";
				$url = str_replace(array("#log_id#"), array($id), $url);
				$serverName = (Context::getCurrent()->getRequest()->isHttps() ? "https" : "http") . "://";
				if(defined("SITE_SERVER_NAME") && SITE_SERVER_NAME <> '')
				{
					$serverName .= SITE_SERVER_NAME;
				}
				else
				{
					$serverName .= Option::get("main", "server_name", "");
				}

				if ($this->isCallback)
				{
					$notifyTag = "CRM|CALLBACK|" . $id;
					$imNotifyEvent = \CCrmNotifierSchemeType::CallbackName;
					$imNotifyMessage = Loc::getMessage(
						'CRM_WEBFORM_RESULT_ENTITY_NOTIFY_SUBJECT_CALL',
						Array(
							"%phone%" => '<a href="' . $url . '">' . htmlspecialcharsbx(
									$this->callbackPhone ? $this->callbackPhone : $this->formData['NAME']
								) . '</a>',
						)
					);
				}
				else
				{
					$notifyTag = "CRM|WEBFORM|" . $id;
					$imNotifyEvent = \CCrmNotifierSchemeType::WebFormName;
					$imNotifyMessage = Loc::getMessage(
						'CRM_WEBFORM_RESULT_ENTITY_NOTIFY_SUBJECT',
						Array(
							"%title%" => '<a href="' . $url . '">' . htmlspecialcharsbx($this->formData['NAME']) . '</a>',
						)
					);
				}

				if (isset($mainEntityLink) && isset($mainEntityName))
				{
					$imNotifyMessage .= "\n";
					$imNotifyMessage .= $this->getEntityLink($mainEntityLink, $mainEntityName);
				}

				$imNotifyMessageOut = $imNotifyMessage . " (". $serverName . $url . ")";
				$imNotifyMessageOut .= "\n\n";
				$imNotifyMessageOut .= Result::formatFieldsByTemplate($this->activityFields);

				$imNotifyFields = array(
					"TO_USER_ID" => $activityFields['RESPONSIBLE_ID'],
					"FROM_USER_ID" => $activityFields['AUTHOR_ID'],
					"NOTIFY_TYPE" => IM_NOTIFY_FROM,
					"NOTIFY_MODULE" => "crm",
					"NOTIFY_EVENT" => $imNotifyEvent,
					"NOTIFY_TAG" => $notifyTag,
					"NOTIFY_MESSAGE" => $imNotifyMessage,
					"NOTIFY_MESSAGE_OUT" => $imNotifyMessageOut
				);
				\CIMNotify::Add($imNotifyFields);
			}
		}
	}

	protected function runAutomation()
	{
		$bindings = array();
		foreach($this->resultEntityPack as $entity)
		{
			$isEntityAdded = !$entity['IS_DUPLICATE'];
			$entityTypeName = $entity['ENTITY_NAME'];
			$entityId = $entity['ITEM_ID'];

			$wasAutomationLaunchedInOperation =
				\CCrmOwnerType::isUseDynamicTypeBasedApproach(\CCrmOwnerType::ResolveID($entityTypeName))
				|| (
					$entityTypeName === \CCrmOwnerType::QuoteName
					&& Crm\Settings\QuoteSettings::getCurrent()->isFactoryEnabled()
				)
			;

			if (!$wasAutomationLaunchedInOperation)
			{
				$errors = array();
				\CCrmBizProcHelper::AutoStartWorkflows(
					\CCrmOwnerType::ResolveID($entityTypeName),
					$entityId,
					$isEntityAdded ? \CCrmBizProcEventType::Create : \CCrmBizProcEventType::Edit,
					$errors
				);

				if($isEntityAdded && empty($entity['IS_AUTOMATION_RUN']))
				{
					$starter = new Automation\Starter(\CCrmOwnerType::ResolveID($entityTypeName), $entityId);
					$starter->runOnAdd();
				}
			}

			$bindings[] = array(
				'OWNER_ID' => $entity['ITEM_ID'],
				'OWNER_TYPE_ID' => \CCrmOwnerType::ResolveID($entity['ENTITY_NAME'])
			);
		}

		if ($this->isCallback)
		{
			Automation\Trigger\CallBackTrigger::execute($bindings, array(
				'WEBFORM_ID' => $this->formId
			));
		}

		Automation\Trigger\WebFormTrigger::execute($bindings, array(
			'WEBFORM_ID' => $this->formId
		));
	}

	protected function prepareFields($fields)
	{
		$entityFields = [];
		foreach($fields as $field)
		{
			$values = $field['values'];
			switch($field['type_original'])
			{
				case 'typed_string':
					$valuesTmp = array();
					$valueIndex = 0;
					foreach($values as $value)
					{
						$valuesTmp['n' . $valueIndex] = array(
							'VALUE' => $value,
							'VALUE_TYPE' => $field['value_type'] ? $field['value_type'] : 'OTHER'
						);
						$valueIndex++;
					}
					$entityFields[$field['entity_name']]['FM'][$field['entity_field_name']] = $valuesTmp;
					continue 2;
					break;

				case 'date':
				case 'datetime':
					foreach($values as $valueIndex => $value)
					{
						if (
							empty($value)
							|| (
								in_array($field['type'], ['date', 'datetime'], true)
								&& Main\Type\DateTime::tryParse($value, DATE_ATOM) !== null
							)
						)
						{
							continue;
						}

						if ($field['type'] === 'date' && !Main\Type\Date::isCorrect($value))
						{
							$values[$valueIndex] = (new Main\Type\Date())->toString();
						}

						if ($field['type'] === 'datetime' && !Main\Type\DateTime::isCorrect($value))
						{
							$values[$valueIndex] = (new Main\Type\DateTime())->toString();
						}
					}
					break;
			}

			$values = $field['multiple_original'] ? $values : $values[0];
			$entityFields[$field['entity_name']][$field['entity_field_name']] = $values;
		}

		return $entityFields;
	}

	protected function createSelector()
	{
		$fields = $this->fields;

		$targetFields = [
			'FM' => []
		];
		$entityTypeNames = [
			\CCrmOwnerType::ContactName,
			\CCrmOwnerType::CompanyName,
			\CCrmOwnerType::LeadName
		];
		foreach ($entityTypeNames as $entityTypeName)
		{
			// check available fields
			if (!isset($fields[$entityTypeName]))
			{
				continue;
			}
			$seedFields = $fields[$entityTypeName];

			// merge multi fields
			if (isset($seedFields['FM']))
			{
				EntityMerger::mergeMultiFields(
					$seedFields['FM'],
					$targetFields['FM']
				);
			}

			// check person fields
			$fieldNameMap = [
				[
					'typeName' => [\CCrmOwnerType::LeadName, \CCrmOwnerType::ContactName],
					'fieldName' => 'NAME',
				],
				[
					'typeName' => [\CCrmOwnerType::LeadName, \CCrmOwnerType::ContactName],
					'fieldName' => 'LAST_NAME',
				],
				[
					'typeName' => [\CCrmOwnerType::LeadName, \CCrmOwnerType::ContactName],
					'fieldName' => 'SECOND_NAME',
				],
				[
					'typeName' => [\CCrmOwnerType::LeadName],
					'fieldName' => 'COMPANY_TITLE',
				],
				[
					'typeName' => [\CCrmOwnerType::CompanyName],
					'fieldName' => 'TITLE',
					'fieldAlias' => 'COMPANY_TITLE'
				],
			];
			foreach ($fieldNameMap as $item)
			{
				if (!in_array($entityTypeName, $item['typeName']))
				{
					continue;
				}

				$fieldName = $item['fieldName'];
				$fieldAlias = isset($item['fieldAlias']) ? $item['fieldAlias'] : $fieldName;

				// skip if target field value filled
				if (isset($targetFields[$fieldName]) && $targetFields[$fieldName])
				{
					continue;
				}

				// skip if seed field not exists
				if (!isset($seedFields[$fieldName]) || !$seedFields[$fieldName])
				{
					continue;
				}

				$targetFields[$fieldAlias] = $seedFields[$fieldName];
			}
		}

		$criteria = ActualEntitySelector::createDuplicateCriteria(
			$targetFields,
			array(
				ActualEntitySelector::SEARCH_PARAM_PHONE,
				ActualEntitySelector::SEARCH_PARAM_EMAIL,
				ActualEntitySelector::SEARCH_PARAM_ORGANIZATION,
				ActualEntitySelector::SEARCH_PARAM_PERSON
			)
		);

		$selector = (new ActualEntitySelector)
			->setCriteria($criteria)
			->enableFullSearch()
			->disableExclusionChecking();

		$scheme = Entity::getSchemes($this->scheme);
		if (!empty($scheme['DYNAMIC']) && !empty($scheme['MAIN_ENTITY']))
		{
			$selector->setDynamicTypeId($scheme['MAIN_ENTITY']);
		}

		$isMainEntityAdded = false;
		foreach ($this->entities as $entity)
		{
			if (empty($entity['typeId']) || empty($entity['id']))
			{
				continue;
			}

			$entityName = \CCrmOwnerType::ResolveName($entity['typeId']);
			if(!$entityName || !$scheme || !in_array($entityName, $scheme['ENTITIES']))
			{
				continue;
			}

			$skipRanking = false;
			if (!in_array($entity['typeId'], [\CCrmOwnerType::Company, \CCrmOwnerType::Contact]))
			{
				$isMainEntityAdded = true;
			}
			else
			{
				$skipRanking = $isMainEntityAdded;
			}
			$selector->setEntity($entity['typeId'], $entity['id'], $skipRanking);
		}

		return $selector->search();
	}

	public function add($schemeId, $fields)
	{
		$this->entityMap = Entity::getMap();
		$this->scheme = $schemeId;
		$this->fields = $this->prepareFields($fields);
		$this->selector = $this->createSelector();
		$this->performTrace();

		$results = $this->fireEvent('onSiteFormFill', [
			'id' => $this->getFormId(),
			'fields' => $this->fields,
			'properties' => $this->placeholders,
			'assignedById' => $this->assignedById,
		]);

		foreach ($results as $result)
		{
			if ($result->getType() === \Bitrix\Main\EventResult::ERROR)
			{
				continue;
			}

			$resultData = $result->getParameters();
			if (isset($resultData['assignedById']) && $resultData['assignedById'] > 0)
			{
				$this->assignedById = $resultData['assignedById'];
			}
		}

		try
		{
			switch($schemeId)
			{
				case Entity::ENUM_ENTITY_SCHEME_CONTACT:
					$this->addClient();
					break;

				case Entity::ENUM_ENTITY_SCHEME_LEAD:
					$this->addLead();
					break;

				case Entity::ENUM_ENTITY_SCHEME_LEAD_INVOICE:
					$this->addLead(['ADD_INVOICE' => true]);
					break;

				case Entity::ENUM_ENTITY_SCHEME_DEAL:
					$this->addDeal();
					break;

				case Entity::ENUM_ENTITY_SCHEME_QUOTE:
					$this->addQuote();
					break;

				case Entity::ENUM_ENTITY_SCHEME_DEAL_INVOICE:
					$this->addDeal(['ADD_INVOICE' => true]);
					break;

				case Entity::ENUM_ENTITY_SCHEME_QUOTE_INVOICE:
					$this->addQuote(['ADD_INVOICE' => true]);
					break;

				case Entity::ENUM_ENTITY_SCHEME_CONTACT_INVOICE:
					$this->addClient(['ADD_INVOICE' => true]);
					break;

				default:
					$scheme = Entity::getSchemes($schemeId);
					if (!$scheme)
					{
						return;
					}

					$dynamicTypeId = null;
					$hasInvoice = false;
					foreach ($scheme['ENTITIES'] as $entityTypeName)
					{
						$entityTypeId = \CCrmOwnerType::resolveId($entityTypeName);
						if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId))
						{
							$dynamicTypeId = $entityTypeId;
						}
						elseif ($entityTypeId === \CCrmOwnerType::Invoice)
						{
							$hasInvoice = true;
						}
					}

					if ($dynamicTypeId)
					{
						$this->addDynamic([
							'DYNAMIC_TYPE_ID' => $dynamicTypeId,
							'ADD_INVOICE' => $hasInvoice
						]);
					}
					break;
			}

			$this->fillTrace();
			$this->addActivity();
			$this->addConsent();
			$this->runAutomation();
			WebFormTrigger::onFormFill($this);

			if(count($this->resultEntityPack) > 0)
			{
				ResultEntityTable::addBatch($this->formId, $this->resultEntityPack);
			}

			$eventData = [
				'id' => $this->getFormId(),
				'fields' => $this->fields,
				'properties' => $this->placeholders,
				'result' => [
					'entities' => $this->getResultEntities(),
				],
			];
			$eventNamePostfix = $this->placeholders['eventNamePostfix'] ?? '';
			if ($eventNamePostfix && preg_match('/\w+/', $eventNamePostfix))
			{
				(new Main\Event('crm', 'onSiteFormFilled' . $eventNamePostfix, $eventData))->send();
			}
			(new Main\Event('crm', 'onSiteFormFilled', $eventData))->send();
		}
		catch(\Exception $e)
		{
			throw $e;
		}
	}

	/**
	 * @param int $resultId Id of result
	 */
	public function setResultId($resultId)
	{
		$this->resultId = $resultId;
	}

	/**
	 * @param int $formId Id of form
	 */
	public function setFormId($formId)
	{
		$this->formId = $formId;
	}

	/**
	 * @return null|int form Id
	 */
	public function getFormId()
	{
		return $this->formId;
	}

	/**
	 * @param array $formData Form data
	 */
	public function setFormData($formData)
	{
		$this->formData = $formData;
	}

	/**
	 * @param string $duplicateMode Duplicate mode code
	 */
	public function setDuplicateMode($duplicateMode)
	{
		$this->duplicateMode = $duplicateMode;
	}

	/**
	 * @param integer $assignedById Assigned by id
	 */
	public function setAssignedById($assignedById)
	{
		$this->assignedById = $assignedById;
	}

	/**
	 * @param integer $currencyId Currency Id
	 */
	public function setCurrencyId($currencyId)
	{
		$this->currencyId = $currencyId;
	}

	/**
	 * @param array $placeholders Placeholders
	 */
	public function setPlaceholders($placeholders = [])
	{
		$this->placeholders = $placeholders;
	}

	/**
	 * Set common fields.
	 *
	 * @param array $commonFields Common fields
	 */
	public function setCommonFields($commonFields = [])
	{
		$this->commonFields = $commonFields;
	}

	/**
	 * Get common fields.
	 *
	 * @return array
	 */
	public function getCommonFields()
	{
		return $this->commonFields + $this->trace->getUtm();
	}

	/**
	 * Set common data.
	 *
	 * @param array $commonData Common data
	 */
	public function setCommonData($commonData = array())
	{
		$this->commonData = $commonData;
	}

	/*
	 * Set callback data.
	 *
	 * @param bool $isCallback Is callback form.
	 * @param string $callbackPhone Callback phone.
	 */
	public function setCallback($isCallback = false, $callbackPhone = null)
	{
		$this->isCallback = $isCallback;
		$this->callbackPhone = $callbackPhone;
	}

	/*
	 * Set callback data.
	 *
	 * @param array $entities Entities.
	 * @return void
	 */
	public function setEntities(array $entities)
	{
		$this->entities = $entities;
	}

	/*
	 * Set applied agreements.
	 *
	 * @param array $agreements Agreements.
	 * @return void
	 */
	public function setAgreements(array $agreements)
	{
		$this->agreements = $agreements;
	}


	/**
	 * Get list of created or existed entities.
	 *
	 * @return array
	 */
	public function getResultEntities()
	{
		$resultEntityPack = array();
		foreach ($this->resultEntityPack as $packItem)
		{
			$resultEntityPack[] = array(
				'ENTITY_TYPE' => $packItem['ENTITY_NAME'],
				'ENTITY_ID' => $packItem['ITEM_ID'],
				'IS_DUPLICATE' => $packItem['IS_DUPLICATE']
			);
		}

		if ($this->activityId)
		{
			$resultEntityPack[] = array(
				'ENTITY_TYPE' => \CCrmOwnerType::ActivityName,
				'ENTITY_ID' => $this->activityId,
				'IS_DUPLICATE' => false
			);
		}

		return $resultEntityPack;
	}

	/**
	 * @param string $entityTypeName Entity Type Name
	 * @return null|int
	 */
	public function getEntityIdByTypeName($entityTypeName)
	{
		foreach ($this->resultEntityPack as $packItem)
		{
			if ($entityTypeName == $packItem['ENTITY_NAME'])
			{
				return $packItem['ITEM_ID'];
			}
		}

		return null;
	}

	private function fixInvoiceFieldsAnonymousUser ($entityFields)
	{
		// invoice properties
		$companyID = isset($entityFields['UF_COMPANY_ID']) ? (int) $entityFields['UF_COMPANY_ID'] : 0;
		$contactID = isset($entityFields['UF_CONTACT_ID']) ? (int) $entityFields['UF_CONTACT_ID'] : 0;

		$entityFields['INVOICE_PROPERTIES'] = array();
		$invoiceEntity = new \CCrmInvoice(false);

		$personTypeID = 0;
		$personTypes = \CCrmPaySystem::getPersonTypeIDs();
		if ($companyID > 0 && isset($personTypes['COMPANY']))
		{
			$personTypeID = (int) $personTypes['COMPANY'];
		}
		else if(isset($personTypes['CONTACT']))
		{
			$personTypeID = (int)$personTypes['CONTACT'];
		}
		$entityFields['PERSON_TYPE_ID'] = $personTypeID;

		$requisiteEntityList = array();
		$requisite = new \Bitrix\Crm\EntityRequisite();
		if ($companyID > 0)
		{
			$requisiteEntityList[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Company, 'ENTITY_ID' => $companyID);
		}

		if ($contactID > 0)
		{
			$requisiteEntityList[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Contact, 'ENTITY_ID' => $contactID);
		}

		$requisiteIdLinked = 0;
		$requisiteInfoLinked = $requisite->getDefaultRequisiteInfoLinked($requisiteEntityList);
		if (is_array($requisiteInfoLinked))
		{
			if (isset($requisiteInfoLinked['REQUISITE_ID']))
			{
				$requisiteIdLinked = (int)$requisiteInfoLinked['REQUISITE_ID'];
			}
		}
		unset($requisiteEntityList, $requisite, $requisiteInfoLinked);

		$properties = $invoiceEntity->GetProperties(0, $personTypeID);
		if(is_array($properties))
		{
			\CCrmInvoice::__RewritePayerInfo($companyID, $contactID, $properties);
			if ($entityFields['PERSON_TYPE_ID'] > 0 && $requisiteIdLinked > 0)
			{
				\CCrmInvoice::rewritePropsFromRequisite(
					$entityFields['PERSON_TYPE_ID'],
					$requisiteIdLinked,
					$properties
				);
			}

			foreach($properties as $property)
			{
				$entityFields['INVOICE_PROPERTIES'][$property['FIELDS']['ID']] = $property['VALUE'];
			}
		}

		return $entityFields;
	}

	/**
	 * @param string $eventName
	 * @param array $eventParams
	 * @return Main\EventResult[]
	 */
	private function fireEvent(string $eventName, array $eventParams): array
	{
		$event = new Main\Event('crm', $eventName, $eventParams);
		$event->send();
		$results = $event->getResults();

		$eventNamePostfix = $this->placeholders['eventNamePostfix'] ?? '';
		if ($eventNamePostfix && preg_match('/\w+/', $eventNamePostfix))
		{
			$eventWithPostfix = new Main\Event('crm', $eventName. $eventNamePostfix, $eventParams);
			$eventWithPostfix->send();
			$results = array_merge($results, $eventWithPostfix->getResults());
		}

		return $results;
	}

	/**
	 * @return string|null
	 */
	private function getEntityLink(string $link, string $entityName): string
	{
		return Loc::getMessage('CRM_WEBFORM_RESULT_ENTITY_NOTIFY_MESSAGE_LINK', [
			'#ENTITY_LINK#' => '<a href="' . $link . '">' . htmlspecialcharsbx($entityName) . '</a>',
		]);
	}

	private function removeIgnoredActivityBindings(array $bindings): array
	{
		foreach ($bindings as $i => $binding)
		{
			if ($binding['OWNER_TYPE_ID'] == \CCrmOwnerType::SmartDocument) // do not create activity for smart document
			{
				unset($bindings[$i]);
			}
		}

		return $bindings;
	}
}

<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm;

use Bitrix\Crm\Automation;
use Bitrix\Crm\EntityManageFacility;
use Bitrix\Crm\Integration\UserConsent as CrmIntegrationUserConsent;
use Bitrix\Crm\Integrity\ActualEntitySelector;
use Bitrix\Crm\Merger\EntityMerger;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\WebForm\Internals\ResultEntityTable;
use Bitrix\Crm\Activity\Provider;
use Bitrix\Crm\Activity\BindingSelector;
use Bitrix\Crm\Integration\Channel\WebFormTracker;
//use Bitrix\Crm\Integration\Channel\SiteButtonTracker as CallBackWebFormTracker;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserConsent\Consent;
use Bitrix\Crm\Settings\LayoutSettings;
use Bitrix\Crm\Tracking;

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

	protected $isCallback = false;
	protected $callbackPhone = null;

	protected $activityId = null;
	protected $contactId = null;
	protected $companyId = null;
	protected $dealId = null;
	protected $leadId = null;
	protected $quoteId = null;
	protected $invoiceId = null;

	protected $resultEntityPack = array();

	/** @var ActualEntitySelector  */
	protected $selector = null;

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

		switch ($entityTypeName)
		{
			case \CCrmOwnerType::CompanyName:
				$rowId = $this->selector->getCompanyId();
				break;

			case \CCrmOwnerType::ContactName:
				$rowId = $this->selector->getContactId();
				break;

			case \CCrmOwnerType::DealName:
				$rowId = $this->isDealDuplicateControlEnabled ?
					$this->selector->getDealId()
					:
					null;
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
				return null;
		}

		if (!$rowId)
		{
			return null;
		}

		/** @var  $entityObject \CCrmContact */
		$entityObject = new $entity['CLASS_NAME'](false);

		$entityMultiFields = array();
		$hasMultiFields = !empty($entity['HAS_MULTI_FIELDS']);
		if ($hasMultiFields)
		{
			$multiFields = \CCrmFieldMulti::GetEntityFields($entityTypeName, $rowId, null);
			foreach($multiFields as $multiField)
			{
				$entityMultiFields[$multiField['TYPE_ID']] = array(
					$multiField['ID'] => array(
						'VALUE' => $multiField['VALUE'],
						'VALUE_TYPE' => $multiField['VALUE_TYPE'],
					)
				);
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
				$entityFields = $entityFieldsDb->Fetch();
				if ($hasMultiFields)
				{
					$entityFields['FM'] = $entityMultiFields;
				}
				foreach ($entityFields as $key => $value)
				{
					if ($value === null)
					{
						unset($entityFields[$key]);
					}
				}
				$merger = new $mergerClass(0, false);
				$merger->mergeFields($fields, $entityFields, false, array('ENABLE_UPLOAD' => true));

				$entityObject->Update($rowId, $entityFields);
				break;

			case self::DUPLICATE_CONTROL_MODE_REPLACE:
				$entityFields = [];
				if ($hasMultiFields)
				{
					$entityFields['FM'] = $entityMultiFields;
				}
				$merger = new $mergerClass(0, false);
				$merger->mergeFields($fields, $entityFields, false, array('ENABLE_UPLOAD' => true));
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

	protected function getEntityFields($entityName)
	{
		if(isset($this->fields[$entityName]))
		{
			$fields = $this->fields[$entityName];
		}
		else
		{
			$fields =  array();
		}

		if(!$this->entityMap)
		{
			$this->entityMap = Entity::getMap();
		}

		if(!isset($this->entityMap[$entityName]['FIELD_AUTO_FILL_TEMPLATE']))
		{
			return $fields;
		}


		foreach($this->entityMap[$entityName]['FIELD_AUTO_FILL_TEMPLATE'] as $fieldName => $fieldProperties)
		{
			if(isset($fields[$fieldName]) && $fields[$fieldName])
			{
				continue;
			}

			$fields[$fieldName] =  $this->replaceDefaultFieldValue($fieldProperties['TEMPLATE']);
		}

		return $fields;
	}

	public function setProductRows($productList)
	{
		foreach($productList as $product)
		{
			$this->productRows[] = array(
				'PRODUCT_ID' => (int) $product['ID'],
				'PRODUCT_NAME' => $product['NAME'],
				'PRICE' => $product['PRICE'],
				'QUANTITY' => 1,
				'VAT_INCLUDED' => isset($product['VAT_INCLUDED']) ? $product['VAT_INCLUDED'] : null,
				'VAT_RATE' => isset($product['VAT_RATE']) ? $product['VAT_RATE'] : null,
			);
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
			$result += $productRow['PRICE'];
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

		foreach($this->presetFields as $presetField)
		{
			if($presetField['ENTITY_NAME'] != $entityName)
			{
				continue;
			}

			$value = $presetField['VALUE'];
			$placeholders = $this->placeholders;
			$placeholders['crm_form_id'] = $this->formId;
			$placeholders['crm_form_name'] = $this->formData['NAME'];
			$placeholders['crm_result_id'] = $this->resultId;
			$fromList = $toList = array();
			foreach ($placeholders as $key => $val)
			{
				$fromList[] = '%' . $key . '%';
				$toList[] = $val;
			}
			$value = str_replace($fromList,	$toList, $value);
			$entityFields[$presetField['FIELD_NAME']] = $value;
		}

		return $entityFields;
	}

	protected function addByEntityName($entityName, $params = array())
	{
		$id = null;

		$entityFields = $this->getEntityFields($entityName);
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

		if(!$isEntityInvoice)
		{
			$entityFields = $entityFields + $this->commonFields;
		}
		$entityFields = $this->fillFieldsByPresetFields($entityName, $entityFields);

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
		$entityClassName = $entity['CLASS_NAME'];

		$isEntityAdded = false;
		$id = $this->findDuplicateEntityId($entityName, $entityFields);
		$facility = new EntityManageFacility($this->selector);
		if(!$id)
		{
			if($isNeedAddProducts && ($isLeadOrQuoteOrDeal || $isEntityInvoice))
			{
				$entityFields["CURRENCY_ID"] = $this->currencyId;
			}

			if($isNeedAddProducts && $isEntityInvoice)
			{
				$entityFields["PRODUCT_ROWS"] = $this->getProductRows(false);
			}

			if($this->isCallback && isset($entityFields['SOURCE_ID']) && $entityFields['SOURCE_ID'] == 'WEBFORM')
			{
				$entityFields['SOURCE_ID'] = 'CALLBACK';
			}

			/** @var \CCrmLead $entityInstance */
			$entityInstance = new $entityClassName(false);
			$addOptions = array('DISABLE_USER_FIELD_CHECK' => true);
			if($isEntityInvoice)
			{
				/** @var \CCrmInvoice $entityInstance */
				$recalculateOptions = false;
				$entityFields = $this->fixInvoiceFieldsAnonymousUser($entityFields);
				$id = $entityInstance->Add($entityFields, $recalculateOptions, SITE_ID, $addOptions);
			}
			else
			{
				$entityFields['WEBFORM_ID'] = $this->formId;
				if($isEntityLead)
				{
					if ($this->duplicateMode == self::DUPLICATE_CONTROL_MODE_NONE)
					{
						$facility->setRegisterMode(EntityManageFacility::REGISTER_MODE_ALWAYS_ADD);
					}
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
			if($isNeedAddProducts && $isLeadOrQuoteOrDeal)
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
						'IS_AUTOMAION_RUN' => false,
					];
				}
			}

			$this->resultEntityPack[] = $resultEntityInfo;

			if (
				!$isEntityAdded
				&& $this->duplicateMode === self::DUPLICATE_CONTROL_MODE_REPLACE
				&& $entityName === \CCrmOwnerType::LeadName
				&& isset($entityFields['STATUS_ID'])
			)
			{
				$previousFields = $entityClassName::GetByID($id, false);
				if ($previousFields['STATUS_ID'] === $entityFields['STATUS_ID'])
				{
					Automation\Factory::runOnStatusChanged(\CCrmOwnerType::ResolveID($entityName), $id);
				}
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

		if(is_array($this->formData['FORM_SETTINGS']) && isset($this->formData['FORM_SETTINGS']['DEAL_DC_ENABLED']))
		{
			$this->isDealDuplicateControlEnabled = $this->formData['FORM_SETTINGS']['DEAL_DC_ENABLED'] === 'Y';
		}

		$params['SET_PRODUCTS'] = true;
		$this->dealId = $this->addByEntityName(\CCrmOwnerType::DealName, $params);

		if ($this->dealId)
		{
/*			if ($this->isCallback)
			{
				CallBackWebFormTracker::getInstance()->registerDeal($this->dealId);
			}
			else
*/			{
				WebFormTracker::getInstance()->registerDeal($this->dealId, array('ORIGIN_ID' => $this->formId));
			}
		}

		if($dealParams['ADD_INVOICE'])
		{
			$this->addInvoice();
		}
	}

	protected function addLead($leadParams = array())
	{
		$params = array();
		$params['SET_PRODUCTS'] = true;
		$this->leadId = $this->addByEntityName(\CCrmOwnerType::LeadName, $params);

		if ($this->leadId)
		{
/*			if ($this->isCallback)
			{
				CallBackWebFormTracker::getInstance()->registerLead($this->leadId);
			}
			else
*/			{
				WebFormTracker::getInstance()->registerLead($this->leadId, array('ORIGIN_ID' => $this->formId));
			}
		}

		if($leadParams['ADD_INVOICE'])
		{
			$this->addClient();
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
		$isAddCompany = isset($this->fields[\CCrmOwnerType::CompanyName]) || $this->isInvoiceSettingsPayerCompany();
		$isAddContact = isset($this->fields[\CCrmOwnerType::ContactName]) || $this->isInvoiceSettingsPayerContact();

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

		$params = array();
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

	protected function addInvoice($params = array())
	{
		if(!isset($params['FIELDS']))
		{
			$params['FIELDS'] = array();
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
		foreach($billList as $billId => $billName)
		{
			$params['FIELDS']['PAY_SYSTEM_ID'] = $billId;
			break;
		}

		$params['SET_PRODUCTS'] = true;
		$this->invoiceId = $this->addByEntityName(\CCrmOwnerType::InvoiceName, $params);
	}

	protected function addConsent()
	{
		if ($this->formData['USE_LICENCE'] != 'Y' || !$this->formData['AGREEMENT_ID'])
		{
			return;
		}

		Consent::addByContext(
			$this->formData['AGREEMENT_ID'],
			CrmIntegrationUserConsent::PROVIDER_CODE,
			$this->activityId
		);
	}

	protected function addTrace()
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

		$string = isset($this->commonData['TRACE']) ? $this->commonData['TRACE'] : null;
		$trace = Tracking\Trace::create($string);
		if ($this->isCallback)
		{
			$trace->addChannel(new Tracking\Channel\Callback($this->formId));
		}
		else
		{
			$trace->addChannel(new Tracking\Channel\Form($this->formId));
		}
		$traceId = $trace->save();
		foreach ($entities as $entity)
		{
			Tracking\Trace::appendEntity(
				$traceId,
				$entity['ENTITY_TYPE_ID'],
				$entity['ENTITY_ID']
			);
		}
	}

	protected function addActivity()
	{
		// prepare bindings
		$bindings = BindingSelector::findBindings($this->selector);
		foreach($this->resultEntityPack as $entity)
		{
			$bindings[] = array(
				'OWNER_ID' => $entity['ITEM_ID'],
				'OWNER_TYPE_ID' => \CCrmOwnerType::ResolveID($entity['ENTITY_NAME'])
			);
		}
		$bindings = BindingSelector::sortBindings($bindings);

		// add activity
		$activityFields = array(
			'TYPE_ID' =>  \CCrmActivityType::Provider,
			'PROVIDER_ID' => Provider\WebForm::PROVIDER_ID,
			'PROVIDER_TYPE_ID' => $this->formId,
			'DIRECTION' => \CCrmActivityDirection::Incoming,
			'ASSOCIATED_ENTITY_ID' => $this->resultId,
			'START_TIME' => new DateTime(),
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
					'IP' => Context::getCurrent()->getRequest()->getRemoteAddress(),
					'LINK' => Script::getUrlContext($this->formData)
				),
				'VISITED_PAGES' => isset($this->commonData['VISITED_PAGES']) ? $this->commonData['VISITED_PAGES'] : array()
			)
		);

		if ($this->isCallback)
		{
			$activityFields['SUBJECT'] = Loc::getMessage(
				'CRM_WEBFORM_RESULT_ENTITY_NOTIFY_SUBJECT_CALL',
				Array(
					"%phone%" => htmlspecialcharsbx($this->callbackPhone ? $this->callbackPhone : $this->formData['NAME']),
				)
			);
		}
		else
		{
			$activityFields['SUBJECT'] = Loc::getMessage(
				'CRM_WEBFORM_RESULT_ENTITY_NOTIFY_SUBJECT',
				Array(
					"%title%" => htmlspecialcharsbx($this->formData['NAME']),
				)
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
				if(defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0)
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
			$errors = array();
			\CCrmBizProcHelper::AutoStartWorkflows(
				\CCrmOwnerType::ResolveID($entityTypeName),
				$entityId,
				$isEntityAdded ? \CCrmBizProcEventType::Create : \CCrmBizProcEventType::Edit,
				$errors
			);

			if($isEntityAdded && empty($entity['IS_AUTOMATION_RUN']))
			{
				Automation\Factory::runOnAdd(\CCrmOwnerType::ResolveID($entityTypeName), $entityId);
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
		$entityFields = array();
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
			}

			$values = $field['multiple_original'] ? $values : $values[0];
			$entityFields[$field['entity_name']][$field['entity_field_name']] = $values;
		}

		return $entityFields;
	}

	protected static function createSelector(array $fields)
	{
		$targetFields = array(
			'FM' => array()
		);
		$entityTypeNames = array(
			\CCrmOwnerType::ContactName,
			\CCrmOwnerType::CompanyName,
			\CCrmOwnerType::LeadName
		);
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
			$fieldNameMap = array(
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
			);
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

		return (new ActualEntitySelector)
			->setCriteria($criteria)
			->enableFullSearch()
			->disableExclusionChecking()
			->search();
	}

	public function add($scheme, $fields)
	{
		$this->entityMap = Entity::getMap();
		$this->scheme = $scheme;
		$this->fields = $this->prepareFields($fields);
		$this->selector = self::createSelector($this->fields);

		try
		{
			switch($scheme)
			{
				case Entity::ENUM_ENTITY_SCHEME_CONTACT:
					$this->addClient();
					break;

				case Entity::ENUM_ENTITY_SCHEME_LEAD:
					$this->addLead();
					break;

				case Entity::ENUM_ENTITY_SCHEME_LEAD_INVOICE:
					$this->addLead(array('ADD_INVOICE' => true));
					break;

				case Entity::ENUM_ENTITY_SCHEME_DEAL:
					$this->addDeal();
					break;

				case Entity::ENUM_ENTITY_SCHEME_QUOTE:
					$this->addQuote();
					break;

				case Entity::ENUM_ENTITY_SCHEME_DEAL_INVOICE:
					$this->addDeal(array('ADD_INVOICE' => true));
					break;

				case Entity::ENUM_ENTITY_SCHEME_QUOTE_INVOICE:
					$this->addQuote(array('ADD_INVOICE' => true));
					break;

				case Entity::ENUM_ENTITY_SCHEME_CONTACT_INVOICE:
					$this->addClient(array('ADD_INVOICE' => true));
					break;
			}

			$this->addTrace();
			$this->addActivity();
			$this->addConsent();
			$this->runAutomation();

			if(count($this->resultEntityPack) > 0)
			{
				ResultEntityTable::addBatch($this->formId, $this->resultEntityPack);
			}
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
	public function setPlaceholders($placeholders = array())
	{
		$this->placeholders = $placeholders;
	}

	/**
	 * Set common fields
	 * @param array $commonFields Common fields
	 */
	public function setCommonFields($commonFields = array())
	{
		$this->commonFields = $commonFields;
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
	 * @param bool $isCallback Is callback form
	 * @param string $callbackPhone Callback phone
	 */
	public function setCallback($isCallback = false, $callbackPhone = null)
	{
		$this->isCallback = $isCallback;
		$this->callbackPhone = $callbackPhone;
	}


	/**
	 * Get list of created or existed entities
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
}

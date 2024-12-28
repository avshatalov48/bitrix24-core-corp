<?php

use Bitrix\Crm;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Main;

if (!CModule::IncludeModule('bizproc'))
{
	return;
}

IncludeModuleLangFile(__DIR__ . '/crm_document.php');

class CCrmDocumentDeal extends CCrmDocument implements IBPWorkflowDocument
{
	static public function GetDocumentFields($documentType)
	{
		$arDocumentID = self::GetDocumentInfo($documentType . '_0');
		if (empty($arDocumentID))
		{
			throw new CBPArgumentNullException('documentId');
		}

		$arResult = self::getEntityFields($arDocumentID['TYPE']);

		self::appendReferenceFields(
			$arResult,
			\CCrmDocumentContact::getEntityFields(\CCrmOwnerType::ContactName),
			\CCrmOwnerType::Contact
		);

		self::appendReferenceFields(
			$arResult,
			\CCrmDocumentCompany::getEntityFields(\CCrmOwnerType::CompanyName),
			\CCrmOwnerType::Company
		);

		return $arResult;
	}

	private static function appendReferenceFields(array &$thisFields, array $referenceFields, $entityTypeId)
	{
		$fieldNamePrefix = \CCrmOwnerType::GetDescription($entityTypeId) . ': ';
		$fieldIdPrefix = \CCrmOwnerType::ResolveName($entityTypeId);

		foreach ($referenceFields as $id => $field)
		{
			if (mb_strpos($id, '.') !== false)
			{
				continue;
			}

			$field['Filterable'] = $field['Editable'] = false;
			$field['Name'] = $fieldNamePrefix.$field['Name'];
			$thisFields[$fieldIdPrefix.'.'.$id] = $field;
		}
	}

	public static function GetDocument($documentId)
	{
		$documentInfo = static::GetDocumentInfo($documentId);

		return new Crm\Integration\BizProc\Document\ValueCollection\Deal(
			CCrmOwnerType::Deal,
			$documentInfo['ID']
		);
	}

	public static function getEntityFields($entityType)
	{
		\Bitrix\Main\Localization\Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/components/bitrix/crm.'.
			mb_strtolower($entityType).'.edit/component.php');

		$printableFieldNameSuffix = ' (' . GetMessage('CRM_FIELD_BP_TEXT') . ')';

		$arResult = static::getVirtualFields() + [
			'ID' => [
				'Name' => GetMessage('CRM_FIELD_ID'),
				'Type' => 'int',
				'Filterable' => true,
				'Editable' => false,
				'Required' => false,
			],
			'TITLE' =>[
				'Name' => GetMessage('CRM_FIELD_TITLE_DEAL'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => true,
			],
			'OPPORTUNITY' => [
				'Name' => GetMessage('CRM_FIELD_OPPORTUNITY'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'CURRENCY_ID' => [
				'Name' => GetMessage('CRM_FIELD_CURRENCY_ID'),
				'Type' => 'select',
				'Options' => CCrmCurrencyHelper::PrepareListItems(),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'OPPORTUNITY_ACCOUNT' => [
				'Name' => GetMessage('CRM_FIELD_OPPORTUNITY_ACCOUNT'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => false,
				'Required' => false,
			],
			'ACCOUNT_CURRENCY_ID' => [
				'Name' => GetMessage('CRM_FIELD_ACCOUNT_CURRENCY_ID'),
				'Type' => 'select',
				'Options' => CCrmCurrencyHelper::PrepareListItems(),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'PROBABILITY' => [
				'Name' => GetMessage('CRM_FIELD_PROBABILITY'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'ASSIGNED_BY_ID' => [
				'Name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID'),
				'Type' => 'user',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
		];

		$arResult += parent::getAssignedByFields();
		$arResult += [
			'CREATED_BY_ID' => [
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_CREATED_BY_ID_DEAL'),
				'Type' => 'user',
			],
			'MODIFY_BY_ID' => [
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_MODIFY_BY_ID'),
				'Type' => 'user',
			],
			'CATEGORY_ID' => [
				'Name' => GetMessage('CRM_FIELD_CATEGORY_ID'),
				'Type' => 'select',
				'Options' => DealCategory::getSelectListItems(true),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false
			],
			'CATEGORY_ID_PRINTABLE' => [
				'Name' => GetMessage('CRM_FIELD_CATEGORY_ID').$printableFieldNameSuffix,
				'Type' => 'string',
				'Filterable' => false,
				'Editable' => false,
				'Required' => false,
			],
			'STAGE_ID' => [
				'Name' => GetMessage('CRM_FIELD_STAGE_ID'),
				'Type' => 'select',
				'Options' => DealCategory::getFullStageList(),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
				'Settings' => array('Groups' => DealCategory::getStageGroupInfos())
			],
			'STAGE_ID_PRINTABLE' => [
				'Name' => GetMessage('CRM_FIELD_STAGE_ID').$printableFieldNameSuffix,
				'Type' => 'string',
				'Filterable' => false,
				'Editable' => false,
				'Required' => false,
			],
			'CLOSED' => [
				'Name' => GetMessage('CRM_FIELD_CLOSED'),
				'Type' => 'bool',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'TYPE_ID' => [
				'Name' => GetMessage('CRM_DOCUMENT_DEAL_TYPE_ID'),
				'Type' => 'select',
				'Options' => CCrmStatus::GetStatusListEx('DEAL_TYPE'),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'COMMENTS' => [
				'Name' => GetMessage('CRM_FIELD_COMMENTS'),
				'Type' => 'text',
				'ValueContentType' => 'bb',
				'Filterable' => false,
				'Editable' => true,
				'Required' => false,
			],
			'BEGINDATE' => [
				'Name' => GetMessage('CRM_FIELD_BEGINDATE'),
				'Type' => 'date',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'CLOSEDATE' => [
				'Name' => GetMessage('CRM_FIELD_CLOSEDATE'),
				'Type' => 'datetime',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'EVENT_DATE' => [
				'Name' => GetMessage('CRM_FIELD_EVENT_DATE'),
				'Type' => 'datetime',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'EVENT_ID' => [
				'Name' => GetMessage('CRM_FIELD_EVENT_ID'),
				'Type' => 'select',
				'Options' => CCrmStatus::GetStatusListEx('EVENT_TYPE'),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'EVENT_DESCRIPTION' => [
				'Name' => GetMessage('CRM_FIELD_EVENT_DESCRIPTION'),
				'Type' => 'text',
				'Filterable' => false,
				'Editable' => true,
				'Required' => false,
			],
			"OPENED" => [
				"Name" => GetMessage("CRM_FIELD_OPENED"),
				"Type" => "bool",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			],
			"LEAD_ID" => [
				"Name" => GetMessage("CRM_FIELD_LEAD_ID"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			],
			"ORIGINATOR_ID" => [
				"Name" => GetMessage("CRM_FIELD_ORIGINATOR_ID"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			],
			"ORIGIN_ID" => [
				"Name" => GetMessage("CRM_FIELD_ORIGIN_ID"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			],
			"CONTACT_ID" => [
				"Name" => GetMessage("CRM_FIELD_CONTACT_ID"),
				"Type" => "UF:crm",
				"Options" => array('CONTACT' => 'Y'),
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
				"Multiple" => false,
			],
			"CONTACT_IDS" => [
				"Name" => GetMessage("CRM_FIELD_CONTACT_IDS"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
				"Multiple" => true,
			],
			"OBSERVER_IDS" => [
				"Name" => GetMessage("CRM_FIELD_OBSERVER_IDS"),
				"Type" => "user",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
				"Multiple" => true,
				'Default' => [],
			],
			"COMPANY_ID" => [
				"Name" => GetMessage("CRM_FIELD_COMPANY_ID"),
				"Type" => "UF:crm",
				"Options" => array('COMPANY' => 'Y'),
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
				"Multiple" => false,
			],
			'SOURCE_ID' => [
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_SOURCE_ID'),
				'Type' => 'select',
				'Options' => CCrmStatus::GetStatusListEx('SOURCE'),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'SOURCE_DESCRIPTION' => [
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_SOURCE_DESCRIPTION'),
				'Type' => 'text',
				'Filterable' => false,
				'Editable' => true,
				'Required' => false,
			],
			"DATE_CREATE" => [
				"Name" => GetMessage("CRM_DEAL_EDIT_FIELD_DATE_CREATE"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			],
			"DATE_MODIFY" => [
				"Name" => GetMessage("CRM_DEAL_EDIT_FIELD_DATE_MODIFY"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			],
			'WEBFORM_ID' => [
				'Name' => GetMessage('CRM_DOCUMENT_WEBFORM_ID'),
				'Type' => 'select',
				'Options' => static::getWebFormSelectOptions(),
				'Filterable' => false,
				'Editable' => false,
				'Required' => false,
			],
			'IS_RETURN_CUSTOMER' => [
				'Name' => GetMessage('CRM_DOCUMENT_DEAL_IS_RETURN_CUSTOMER'),
				'Type' => 'bool',
				'Editable' => false,
			],
			"ORDER_IDS" => [
				"Name" => GetMessage("CRM_FIELD_ORDER_IDS"),
				"Type" => "int",
				"Multiple" => true,
			],
			'IS_REPEATED_APPROACH' => [
				'Name' => GetMessage('CRM_DOCUMENT_DEAL_IS_REPEATED_APPROACH'),
				'Type' => 'bool',
				'Editable' => false,
			],
			"PRODUCT_IDS" => [
				"Name" => GetMessage("CRM_DOCUMENT_FIELD_PRODUCT_IDS"),
				"Type" => "int",
				"Multiple" => true,
			],
			"PRODUCT_IDS_PRINTABLE" => [
				"Name" => GetMessage("CRM_DOCUMENT_FIELD_PRODUCT_IDS") . $printableFieldNameSuffix,
				"Type" => "text",
			],
			'TRACKING_SOURCE_ID' => [
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_TRACKING_SOURCE_ID'),
				'Type' => 'select',
				'Options' => array_column(Crm\Tracking\Provider::getActualSources(), 'NAME','ID'),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
		];

		$arResult += static::getCommunicationFields();

		global $USER_FIELD_MANAGER;
		$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, 'CRM_DEAL');
		$CCrmUserType->AddBPFields($arResult, array('PRINTABLE_SUFFIX' => GetMessage("CRM_FIELD_BP_TEXT")));

		//append UTM fields
		$arResult += parent::getUtmFields();

		//append FORM fields
		$arResult += parent::getSiteFormFields(CCrmOwnerType::Deal);

		return $arResult;
	}

	/**
	 * @deprecated
	 * @see Crm\Integration\BizProc\Document\ValueCollection\Deal
	 */
	public static function PrepareDocument(array &$arFields)
	{
	}

	public static function CreateDocument($parentDocumentId, $arFields)
	{
		if(!is_array($arFields))
		{
			throw new Exception('Entity fields must be array');
		}

		global $DB;
		$arDocumentID = self::GetDocumentInfo($parentDocumentId);
		if ($arDocumentID == false)
		{
			$arDocumentID['TYPE'] = $parentDocumentId;
		}

		$arFields = self::performTypeCast($arDocumentID, $arFields);
		$arFields = self::performTypeCast4CategoryAndStage($arFields);

		$useTransaction = static::shouldUseTransaction();

		if ($useTransaction)
		{
			$DB->StartTransaction();
		}

		$CCrmEntity = new CCrmDeal(false);
		$id = $CCrmEntity->Add(
			$arFields,
			true,
			[
				'DISABLE_USER_FIELD_CHECK' => true,
				'REGISTER_SONET_EVENT' => true,
				'CURRENT_USER' => static::getSystemUserId(),
			]
		);

		if (!$id || $id <= 0)
		{
			if ($useTransaction)
			{
				$DB->Rollback();
			}
			throw new Exception($CCrmEntity->LAST_ERROR);
		}

		if (isset($arFields['TRACKING_SOURCE_ID']))
		{
			Crm\Tracking\UI\Details::saveEntityData(\CCrmOwnerType::Deal, $id, $arFields);
		}

		if (COption::GetOptionString('crm', 'start_bp_within_bp', 'N') == 'Y')
		{
			$CCrmBizProc = new CCrmBizProc('DEAL');
			if (false === $CCrmBizProc->CheckFields(false, true))
			{
				if ($useTransaction)
				{
					$DB->Rollback();
				}
				throw new Exception($CCrmBizProc->LAST_ERROR);
			}

			if ($id && $id > 0 && !$CCrmBizProc->StartWorkflow($id))
			{
				if ($useTransaction)
				{
					$DB->Rollback();
				}
				throw new Exception($CCrmBizProc->LAST_ERROR);
			}
		}

		//region automation
		$starter = new Crm\Automation\Starter(\CCrmOwnerType::Deal, $id);
		$starter->setContextToBizproc()->runOnAdd();
		//endregion

		if ($id && $id > 0 && $useTransaction)
		{
			$DB->Commit();
		}

		return $id;
	}

	public static function UpdateDocument($documentId, $arFields, $modifiedById = null)
	{
		global $DB;

		if(empty($arFields))
		{
			return;
		}

		$arDocumentID = self::GetDocumentInfo($documentId);
		if (empty($arDocumentID))
		{
			throw new CBPArgumentNullException('documentId');
		}

		$dbDocumentList = CCrmDeal::GetListEx(
			[],
			[
				'ID' => $arDocumentID['ID'],
				'CHECK_PERMISSIONS' => 'N',
			],
			false,
			false,
			['ID', 'CATEGORY_ID', 'STAGE_ID']
		);

		$arPresentFields = $dbDocumentList->Fetch();
		if (!is_array($arPresentFields))
		{
			throw new Exception(GetMessage('CRM_DOCUMENT_ELEMENT_IS_NOT_FOUND'));
		}

		$arFields = self::performTypeCast($arDocumentID, $arFields, true);

		//region Category & Stage
		$categoryID = isset($arPresentFields['CATEGORY_ID']) ? (int)$arPresentFields['CATEGORY_ID'] : 0;
		if(isset($arFields['CATEGORY_ID']) && $arFields['CATEGORY_ID'] != $categoryID)
		{
			throw new Exception(GetMessage('CRM_DOCUMENT_DEAL_CATEGORY_CHANGE_ERROR'));
		}

		$arFields = self::performTypeCast4CategoryAndStage($arFields, $categoryID);

		//endregion

		if(empty($arFields))
		{
			return;
		}

		$useTransaction = static::shouldUseTransaction();

		if ($useTransaction)
		{
			$DB->StartTransaction();
		}

		if ($modifiedById > 0)
		{
			$arFields['MODIFY_BY_ID'] = $modifiedById;
		}

		if (isset($arFields['OPPORTUNITY']))
		{
			$arFields['IS_MANUAL_OPPORTUNITY'] = $arFields['OPPORTUNITY'] > 0 ? 'Y' : 'N';
		}

		$dealUpdateAction = new Crm\Reservation\Component\DealUpdateAction($arDocumentID['ID']);
		$dealUpdateAction->before($arFields, static function () use ($useTransaction, $DB) {
			if ($useTransaction)
			{
				$DB->Rollback();
			}

			throw new Exception('Reservation before error');
		});

		$CCrmEntity = new CCrmDeal(false);
		$res = $CCrmEntity->Update(
			$arDocumentID['ID'],
			$arFields,
			true,
			true,
			[
				'DISABLE_USER_FIELD_CHECK' => true,
				'REGISTER_SONET_EVENT' => true,
				'CURRENT_USER' => $modifiedById ?? static::getSystemUserId(),
			]
		);

		if (!$res)
		{
			if ($useTransaction)
			{
				$DB->Rollback();
			}
			throw new Exception($CCrmEntity->LAST_ERROR);
		}

		$dealUpdateAction->after(static function (Main\Result $processInventoryManagementResult) use ($useTransaction, $DB) {
			if ($useTransaction)
			{
				$DB->Rollback();
			}

			throw new Exception(implode(', ', $processInventoryManagementResult->getErrorMessages()));
		});

		if (isset($arFields['TRACKING_SOURCE_ID']))
		{
			Crm\Tracking\UI\Details::saveEntityData(
				\CCrmOwnerType::Deal,
				$arDocumentID['ID'],
				$arFields
			);
		}

		if (COption::GetOptionString('crm', 'start_bp_within_bp', 'N') == 'Y')
		{
			$CCrmBizProc = new CCrmBizProc('DEAL');
			if (false === $CCrmBizProc->CheckFields($arDocumentID['ID'], true))
			{
				if ($useTransaction)
				{
					$DB->Rollback();
				}
				throw new Exception($CCrmBizProc->LAST_ERROR);
			}

			if ($res && !$CCrmBizProc->StartWorkflow($arDocumentID['ID']))
			{
				if ($useTransaction)
				{
					$DB->Rollback();
				}
				throw new Exception($CCrmBizProc->LAST_ERROR);
			}
		}

		//region automation
		$starter = new Crm\Automation\Starter(\CCrmOwnerType::Deal, $arDocumentID['ID']);
		$starter->setContextToBizproc()->runOnUpdate($arFields, $arPresentFields);
		//endregion

		if ($res && $useTransaction)
		{
			$DB->Commit();
		}
	}

	public static function getDocumentName($documentId)
	{
		$arDocumentID = self::GetDocumentInfo($documentId);
		$dbRes = CCrmDeal::GetListEx(
			[],
			[
				'=ID' => $arDocumentID['ID'],
				'CHECK_PERMISSIONS' => 'N',
			],
			false,
			false,
			['TITLE']
		);
		$arRes = $dbRes ? $dbRes->Fetch() : null;
		return $arRes ? $arRes['TITLE'] : '';
	}

	public static function getDocumentCategories($documentType)
	{
		$factory = Crm\Service\Container::getInstance()->getFactory(CCrmOwnerType::Deal);
		$categories = null;

		if ($factory->isCategoriesSupported())
		{
			$categories = [];
			foreach ($factory->getCategories() as $category)
			{
				$categories[$category->getId()] = [
					'id' => $category->getId(),
					'name' => $category->getName(),
				];
			}
		}

		return $categories;
	}

	public static function getDocumentCategoryId(string $documentId): ?int
	{
		$documentInfo = self::GetDocumentInfo($documentId);
		$factory = Crm\Service\Container::getInstance()->getFactory(CCrmOwnerType::Deal);

		if ($factory && $factory?->isCategoriesSupported())
		{
			$entity = $factory->getItem($documentInfo['ID'], ['CATEGORY_ID']);
			if ($entity)
			{
				return $entity->getCategoryId();
			}
		}

		return null;
	}

	public static function normalizeDocumentId($documentId)
	{
		return parent::normalizeDocumentIdInternal(
			$documentId,
			CCrmOwnerType::DealName,
			CCrmOwnerTypeAbbr::Deal
		);
	}

	public static function createAutomationTarget($documentType)
	{
		return Crm\Automation\Factory::createTarget(\CCrmOwnerType::Deal);
	}

	private static function performTypeCast(array $documentInfo, array $fields, bool $isUpdate = false): array
	{
		$documentId = $documentInfo['TYPE'] . '_' . ($isUpdate ? $documentInfo['ID'] : '0');
		$complexDocumentId = [$documentInfo['DOCUMENT_TYPE'][0], $documentInfo['DOCUMENT_TYPE'][1], $documentId];

		$documentFields = self::GetDocumentFields($documentInfo['TYPE']);

		$keys = array_keys($fields);
		foreach ($keys as $key)
		{
			if (!array_key_exists($key, $documentFields))
			{
				//Fix for issue #40374
				unset($fields[$key]);
				continue;
			}

			$fields[$key] = (is_array($fields[$key]) && !CBPHelper::IsAssociativeArray($fields[$key]))
				? $fields[$key]
				: [$fields[$key]]
			;

			if ($documentFields[$key]['Type'] == 'user')
			{
				$fields[$key] = \CBPHelper::extractUsers($fields[$key], $complexDocumentId);;
			}
			elseif ($documentFields[$key]['Type'] == 'select' && mb_substr($key, 0, 3) == 'UF_')
			{
				self::InternalizeEnumerationField('CRM_DEAL', $fields, $key);
			}
			elseif ($documentFields[$key]['Type'] == 'file')
			{
				$fields[$key] = static::castFileFieldValues(
					$documentInfo['ID'],
					\CCrmOwnerType::Deal,
					$key,
					$fields[$key],
				);
			}
			elseif ($documentFields[$key]['Type'] == 'S:HTML')
			{
				foreach ($fields[$key] as &$value)
				{
					$value = ['VALUE' => $value];
				}
				unset($value);
			}
			elseif ($documentFields[$key]['Type'] === 'string' && is_array($fields[$key]))
			{
				$fields[$key] = \CBPHelper::makeArrayFlat($fields[$key]);
			}

			if (!($documentFields[$key]['Multiple'] ?? false) && is_array($fields[$key]))
			{
				if (count($fields[$key]) > 0)
				{
					$a = array_values($fields[$key]);
					$fields[$key] = $a[0];
				}
				else
				{
					$fields[$key] = null;
				}
			}
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = static::sanitizeCommentsValue($fields['COMMENTS']);
		}
		if(isset($fields['BEGINDATE']))
		{
			if ($fields['BEGINDATE'] instanceof Main\Type\Date)
			{
				$fields['BEGINDATE'] = (string)$fields['BEGINDATE'];
			}
			elseif(
				is_object($fields['BEGINDATE'])
				&& method_exists($fields['BEGINDATE'], '__toString')
				&& Main\Type\Date::isCorrect((string)$fields['BEGINDATE'])
			)
			{
				$fields['BEGINDATE'] = (string)$fields['BEGINDATE'];
			}
		}
		if(isset($fields['CLOSEDATE']))
		{
			if ($fields['CLOSEDATE'] instanceof Main\Type\Date)
			{
				$fields['CLOSEDATE'] = (string)$fields['CLOSEDATE'];
			}
			elseif(
				is_object($fields['CLOSEDATE'])
				&& method_exists($fields['CLOSEDATE'], '__toString')
				&& Main\Type\Date::isCorrect((string)$fields['CLOSEDATE'])
			)
			{
				$fields['CLOSEDATE'] = (string)$fields['CLOSEDATE'];
			}
		}

		return $fields;
	}

	private static function performTypeCast4CategoryAndStage(array $fields, int $presentCategoryID = null): array
	{
		if (!isset($fields['STAGE_ID']))
		{
			return $fields;
		}

		if($fields['STAGE_ID'] === '')
		{
			unset($fields['STAGE_ID']);

			return $fields;
		}

		$stageID = $fields['STAGE_ID'];
		$stageCategoryID = DealCategory::resolveFromStageID($stageID);
		if($presentCategoryID === null && !isset($fields['CATEGORY_ID']))
		{
			$fields['CATEGORY_ID'] = $stageCategoryID;

			return $fields;
		}

		$categoryID = $presentCategoryID ?? (int)$fields['CATEGORY_ID'];
		if($categoryID !== $stageCategoryID)
		{
			throw new Exception(
				GetMessage(
					'CRM_DOCUMENT_DEAL_STAGE_MISMATCH_ERROR',
					[
						'#CATEGORY#' => DealCategory::getName($categoryID),
						'#TARG_CATEGORY#' => DealCategory::getName($stageCategoryID),
						'#TARG_STAGE#' => DealCategory::getStageName($stageID, $stageCategoryID)
					]
				)
			);
		}

		return $fields;
	}

	public static function createTestDocument(string $documentType, array $fields, int $createdById): ?string
	{
		if (empty($fields))
		{
			return null;
		}

		$documentInfo = self::GetDocumentInfo($documentType);
		if (empty($documentInfo))
		{
			$documentInfo['TYPE'] = $documentType;
		}

		$fields = self::performTypeCast($documentInfo, $fields);
		$fields = self::performTypeCast4CategoryAndStage($fields);

		$deal = new CCrmDeal(true);
		$id = $deal->Add(
			$fields,
			true,
			[
				'DISABLE_USER_FIELD_CHECK' => true,
				'REGISTER_SONET_EVENT' => true,
				'CURRENT_USER' => $createdById,
			]
		);

		if (!$id || $id <= 0)
		{
			throw new Exception($deal->LAST_ERROR);
		}

		if (isset($arFields['TRACKING_SOURCE_ID']))
		{
			Crm\Tracking\UI\Details::saveEntityData(\CCrmOwnerType::Deal, $id, $arFields);
		}

		$CCrmBizProc = new CCrmBizProc('DEAL');

		if (false === $CCrmBizProc->CheckFields(false, true))
		{
			throw new Exception($CCrmBizProc->LAST_ERROR);
		}

		if (!$CCrmBizProc->StartWorkflow($id))
		{
			throw new Exception($CCrmBizProc->LAST_ERROR);
		}

		// no automation

		return \CCrmOwnerType::DealName . '_' . $id;
	}
}

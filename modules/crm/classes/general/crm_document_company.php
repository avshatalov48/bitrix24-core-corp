<?php

use Bitrix\Crm;
use Bitrix\Crm\Restriction\RestrictionManager;

if (!CModule::IncludeModule('bizproc'))
	return;

IncludeModuleLangFile(__DIR__."/crm_document.php");

class CCrmDocumentCompany extends CCrmDocument implements IBPWorkflowDocument
{
	public static function GetDocumentFields($documentType)
	{
		$arDocumentID = self::GetDocumentInfo($documentType.'_0');
		if (empty($arDocumentID))
			throw new CBPArgumentNullException('documentId');

		$arResult = self::getEntityFields($arDocumentID['TYPE']);

		return $arResult;
	}

	public static function GetDocument($documentId)
	{
		$documentInfo = static::GetDocumentInfo($documentId);

		return new Crm\Integration\BizProc\Document\ValueCollection\Company(
			CCrmOwnerType::Company,
			$documentInfo['ID']
		);
	}

	public static function getEntityFields($entityType)
	{
		\Bitrix\Main\Localization\Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/components/bitrix/crm.'.
			mb_strtolower($entityType).'.edit/component.php');

		$printableFieldNameSuffix = ' ('.GetMessage('CRM_FIELD_BP_TEXT').')';

		$arResult = static::getVirtualFields() + array(
			'ID' => array(
				'Name' => GetMessage('CRM_FIELD_ID'),
				'Type' => 'int',
				'Filterable' => true,
				'Editable' => false,
				'Required' => false,
			),
			'TITLE' => array(
				'Name' => GetMessage('CRM_FIELD_TITLE_COMPANY'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => true,
			),
			'LOGO' => array(
				'Name' => GetMessage('CRM_FIELD_LOGO'),
				'Type' => 'file',
				'Filterable' => false,
				'Editable' => true,
				'Required' => false,
			),
			'COMPANY_TYPE' => array(
				'Name' => GetMessage('CRM_FIELD_COMPANY_TYPE'),
				'Type' => 'select',
				'Options' => CCrmStatus::GetStatusListEx('COMPANY_TYPE'),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'INDUSTRY' => array(
				'Name' => GetMessage('CRM_FIELD_INDUSTRY'),
				'Type' => 'select',
				'Options' => CCrmStatus::GetStatusListEx('INDUSTRY'),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'EMPLOYEES' => array(
				'Name' => GetMessage('CRM_FIELD_EMPLOYEES'),
				'Type' => 'select',
				'Options' => CCrmStatus::GetStatusListEx('EMPLOYEES'),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'REVENUE' => array(
				'Name' => GetMessage('CRM_FIELD_REVENUE'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'CURRENCY_ID' => array(
				'Name' => GetMessage('CRM_FIELD_CURRENCY_ID'),
				'Type' => 'select',
				'Options' => CCrmCurrencyHelper::PrepareListItems(),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'ASSIGNED_BY_ID' => array(
				'Name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID'),
				'Type' => 'user',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
		);

		$arResult += parent::getAssignedByFields();
		$arResult += array(
			'CREATED_BY_ID' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_CREATED_BY_ID_COMPANY'),
				'Type' => 'user',
			),
			'MODIFY_BY_ID' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_MODIFY_BY_ID'),
				'Type' => 'user',
			),
			'COMMENTS' => array(
				'Name' => GetMessage('CRM_FIELD_COMMENTS'),
				'Type' => 'text',
				'ValueContentType' => 'bb',
				'Filterable' => false,
				'Editable' => true,
				'Required' => false,
			),
			'EMAIL' => array(
				'Name' => GetMessage('CRM_FIELD_EMAIL'),
				'Type' => 'email',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'PHONE' => array(
				'Name' => GetMessage('CRM_FIELD_PHONE'),
				'Type' => 'phone',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'WEB' => array(
				'Name' => GetMessage('CRM_FIELD_WEB'),
				'Type' => 'web',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'IM' => array(
				'Name' => GetMessage('CRM_FIELD_MESSENGER'),
				'Type' => 'im',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'ADDRESS' => array(
				'Name' => GetMessage('CRM_FIELD_ADDRESS'),
				'Type' => 'text',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'ADDRESS_LEGAL' => array(
				'Name' => GetMessage('CRM_FIELD_ADDRESS_LEGAL'),
				'Type' => 'text',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'BANKING_DETAILS' => array(
				'Name' => GetMessage('CRM_FIELD_BANKING_DETAILS'),
				'Type' => 'text',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			"OPENED" => array(
				"Name" => GetMessage("CRM_FIELD_OPENED"),
				"Type" => "bool",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"LEAD_ID" => array(
				"Name" => GetMessage("CRM_FIELD_LEAD_ID"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"ORIGINATOR_ID" => array(
				"Name" => GetMessage("CRM_FIELD_ORIGINATOR_ID"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"ORIGIN_ID" => array(
				"Name" => GetMessage("CRM_FIELD_ORIGIN_ID"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"CONTACT_ID" => array(
				"Name" => GetMessage("CRM_FIELD_CONTACT_ID"),
				"Type" => "UF:crm",
				"Options" => array('CONTACT' => 'Y'),
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
				"Multiple" => true,
			),
			"DATE_CREATE" => array(
				"Name" => GetMessage("CRM_COMPANY_EDIT_FIELD_DATE_CREATE"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"DATE_MODIFY" => array(
				"Name" => GetMessage("CRM_COMPANY_EDIT_FIELD_DATE_MODIFY"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			'WEBFORM_ID' => array(
				'Name' => GetMessage('CRM_DOCUMENT_WEBFORM_ID'),
				'Type' => 'select',
				'Options' => static::getWebFormSelectOptions(),
				'Filterable' => false,
				'Editable' => false,
				'Required' => false,
			),
			'TRACKING_SOURCE_ID' => [
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_TRACKING_SOURCE_ID'),
				'Type' => 'select',
				'Options' => array_column(Crm\Tracking\Provider::getActualSources(), 'NAME','ID'),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			"OBSERVER_IDS" => [
				"Name" => GetMessage("CRM_FIELD_OBSERVER_IDS"),
				"Type" => "user",
				"Editable" => !RestrictionManager::getObserversFieldRestriction(\CCrmOwnerType::Company)->isExceeded(),
				"Required" => false,
				"Multiple" => true,
				'Default' => [],
			],
		);

		$arResult += static::getCommunicationFields();

		$ar =  CCrmFieldMulti::GetEntityTypeList();
		foreach ($ar as $typeId => $arFields)
		{
			$arResult[$typeId.'_PRINTABLE'] = array(
				'Name' => GetMessage("CRM_FIELD_MULTI_".$typeId).$printableFieldNameSuffix,
				'Type' => 'string',
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			);
			foreach ($arFields as $valueType => $valueName)
			{
				$arResult[$typeId.'_'.$valueType] = array(
					'Name' => $valueName,
					'Type' => 'string',
					"Filterable" => true,
					"Editable" => false,
					"Required" => false,
				);
				$arResult[$typeId.'_'.$valueType.'_PRINTABLE'] = array(
					'Name' => $valueName.$printableFieldNameSuffix,
					'Type' => 'string',
					"Filterable" => true,
					"Editable" => false,
					"Required" => false,
				);
			}
		}

		global $USER_FIELD_MANAGER;
		$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, 'CRM_COMPANY');
		$CCrmUserType->AddBPFields($arResult, array('PRINTABLE_SUFFIX' => GetMessage("CRM_FIELD_BP_TEXT")));

		//append UTM fields
		$arResult += parent::getUtmFields();

		//append FORM fields
		$arResult += parent::getSiteFormFields(CCrmOwnerType::Company);

		return $arResult;
	}

	public static function CreateDocument($parentDocumentId, $arFields)
	{
		if(!is_array($arFields))
		{
			throw new \Bitrix\Main\ArgumentException("Entity fields must be array");
		}

		global $DB;
		$arDocumentID = self::GetDocumentInfo($parentDocumentId);
		if ($arDocumentID == false)
			$arDocumentID['TYPE'] = $parentDocumentId;

		$arDocumentFields = self::GetDocumentFields($arDocumentID['TYPE']);

		$arKeys = array_keys($arFields);
		foreach ($arKeys as $key)
		{
			if (!array_key_exists($key, $arDocumentFields))
			{
				//Fix for issue #40374
				unset($arFields[$key]);
				continue;
			}

			$fieldType = $arDocumentFields[$key]["Type"];
			if (in_array($fieldType, array("phone", "email", "im", "web"), true))
			{
				CCrmDocument::PrepareEntityMultiFields($arFields, mb_strtoupper($fieldType));
				continue;
			}

			$arFields[$key] = (is_array($arFields[$key]) && !CBPHelper::IsAssociativeArray($arFields[$key])) ? $arFields[$key] : array($arFields[$key]);
			if ($fieldType == "user")
			{
				$arFields[$key] = \CBPHelper::extractUsers($arFields[$key], $arDocumentID['DOCUMENT_TYPE']);
			}
			elseif ($fieldType == "select" && mb_substr($key, 0, 3) == "UF_")
			{
				self::InternalizeEnumerationField('CRM_COMPANY', $arFields, $key);
			}
			elseif ($fieldType == "file")
			{
				$arFileOptions = array('ENABLE_ID' => true);
				foreach ($arFields[$key] as &$value)
				{
					//Issue #40380. Secure URLs and file IDs are allowed.
					$file = false;
					CCrmFileProxy::TryResolveFile($value, $file, $arFileOptions);
					$value = $file;
				}
				unset($value);
			}
			elseif ($fieldType == "S:HTML")
			{
				foreach ($arFields[$key] as &$value)
				{
					$value = array("VALUE" => $value);
				}
				unset($value);
			}

			if (!($arDocumentFields[$key]["Multiple"] ?? false) && is_array($arFields[$key]))
			{
				if (count($arFields[$key]) > 0)
				{
					$a = array_values($arFields[$key]);
					$arFields[$key] = $a[0];
				}
				else
				{
					$arFields[$key] = null;
				}
			}
		}

		if (isset($arFields['CONTACT_ID']) && !is_array($arFields['CONTACT_ID']))
			$arFields['CONTACT_ID'] = array($arFields['CONTACT_ID']);

		if(isset($arFields['COMMENTS']))
		{
			$arFields['COMMENTS'] = static::sanitizeCommentsValue($arFields['COMMENTS']);
		}

		if(isset($arFields['ADDRESS_LEGAL']))
		{
			$arFields['REG_ADDRESS'] = $arFields['ADDRESS_LEGAL'];
			unset($arFields['ADDRESS_LEGAL']);
		}

		$useTransaction = static::shouldUseTransaction();

		if ($useTransaction)
		{
			$DB->StartTransaction();
		}

		$CCrmEntity = new CCrmCompany(false);
		$ID = $CCrmEntity->Add(
			$arFields,
			true,
			[
				'DISABLE_USER_FIELD_CHECK' => true,
				'REGISTER_SONET_EVENT' => true,
				'CURRENT_USER' => static::getSystemUserId(),
			]
		);

		if ($ID <= 0)
		{
			if ($useTransaction)
			{
				$DB->Rollback();
			}
			throw new \Bitrix\Main\SystemException($CCrmEntity->LAST_ERROR);
		}

		//region Try to create requisite
		if((isset($arFields['ADDRESS']) && $arFields['ADDRESS'] !== '') ||
			(isset($arFields['REG_ADDRESS']) && $arFields['REG_ADDRESS'] !== ''))
		{
			$presetID = \Bitrix\Crm\EntityRequisite::getDefaultPresetId(CCrmOwnerType::Company);
			if($presetID > 0)
			{
				$converter = new \Bitrix\Crm\Requisite\AddressRequisiteConverter(CCrmOwnerType::Company, $presetID, false);
				$converter->processEntity($ID);
			}
		}
		//endregion

		if (COption::GetOptionString('crm', 'start_bp_within_bp', 'N') == 'Y')
		{
			$CCrmBizProc = new CCrmBizProc(CCrmOwnerType::CompanyName);
			if ($CCrmBizProc->CheckFields(false, true) === false)
			{
				throw new \Bitrix\Main\SystemException($CCrmBizProc->LAST_ERROR);
			}

			if (!$CCrmBizProc->StartWorkflow($ID))
			{
				if ($useTransaction)
				{
					$DB->Rollback();
				}
				throw new \Bitrix\Main\SystemException($CCrmBizProc->LAST_ERROR);
			}
		}

		if (isset($arFields['TRACKING_SOURCE_ID']))
		{
			Crm\Tracking\UI\Details::saveEntityData(\CCrmOwnerType::Company, $ID, $arFields);
		}

		if ($useTransaction)
		{
			$DB->Commit();
		}

		return $ID;
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
			throw new CBPArgumentNullException('documentId');

		$dbDocumentList = CCrmCompany::GetList(
			array(),
			array('ID' => $arDocumentID['ID'], "CHECK_PERMISSIONS" => "N"),
			array('ID')
		);

		$arResult = $dbDocumentList->Fetch();
		if (!$arResult)
			throw new \Bitrix\Main\SystemException(GetMessage('CRM_DOCUMENT_ELEMENT_IS_NOT_FOUND'));

		$complexDocumentId = [$arDocumentID['DOCUMENT_TYPE'][0], $arDocumentID['DOCUMENT_TYPE'][1], $documentId];
		$arDocumentFields = self::GetDocumentFields($arDocumentID['TYPE']);

		$arKeys = array_keys($arFields);
		foreach ($arKeys as $key)
		{
			if (!array_key_exists($key, $arDocumentFields))
			{
				//Fix for issue #40374
				unset($arFields[$key]);
				continue;
			}

			$fieldType = $arDocumentFields[$key]["Type"];
			if (in_array($fieldType, array("phone", "email", "im", "web"), true))
			{
				CCrmDocument::PrepareEntityMultiFields($arFields, mb_strtoupper($fieldType));
				continue;
			}

			$arFields[$key] = (is_array($arFields[$key]) && !CBPHelper::IsAssociativeArray($arFields[$key])) ? $arFields[$key] : array($arFields[$key]);
			if ($fieldType == "user")
			{
				$arFields[$key] = \CBPHelper::extractUsers($arFields[$key], $complexDocumentId);
			}
			elseif ($fieldType == "select" && mb_substr($key, 0, 3) == "UF_")
			{
				self::InternalizeEnumerationField('CRM_COMPANY', $arFields, $key);
			}
			elseif ($fieldType == "file")
			{
				$arFields[$key] = static::castFileFieldValues(
					$arDocumentID['ID'],
					\CCrmOwnerType::Company,
					$key,
					$arFields[$key],
				);
			}
			elseif ($fieldType == "S:HTML")
			{
				foreach ($arFields[$key] as &$value)
				{
					$value = array("VALUE" => $value);
				}
				unset($value);
			}

			if (!$arDocumentFields[$key]["Multiple"] && is_array($arFields[$key]))
			{
				if (count($arFields[$key]) > 0)
				{
					$a = array_values($arFields[$key]);
					$arFields[$key] = $a[0];
				}
				else
				{
					$arFields[$key] = null;
				}
			}
		}

		if (isset($arFields['CONTACT_ID']) && !is_array($arFields['CONTACT_ID']))
			$arFields['CONTACT_ID'] = array($arFields['CONTACT_ID']);

		if(isset($arFields['COMMENTS']) && $arFields['COMMENTS'] !== '')
		{
			$arFields['COMMENTS'] = static::sanitizeCommentsValue($arFields['COMMENTS']);
		}

		if(isset($arFields['ADDRESS_LEGAL']))
		{
			$arFields['REG_ADDRESS'] = $arFields['ADDRESS_LEGAL'];
			unset($arFields['ADDRESS_LEGAL']);
		}

		$useTransaction = static::shouldUseTransaction();

		if ($useTransaction)
		{
			$DB->StartTransaction();
		}

		$CCrmEntity = new CCrmCompany(false);

		if ($modifiedById > 0)
		{
			$arFields['MODIFY_BY_ID'] = $modifiedById;
		}

		$res = $CCrmEntity->Update(
			$arDocumentID['ID'],
			$arFields,
			true,
			true,
			[
				'DISABLE_USER_FIELD_CHECK' => true,
				'REGISTER_SONET_EVENT' => true,
				'CURRENT_USER' => $modifiedById ?? static::getSystemUserId()
			]
		);

		if (!$res)
		{
			if ($useTransaction)
			{
				$DB->Rollback();
			}
			throw new \Bitrix\Main\SystemException($CCrmEntity->LAST_ERROR);
		}

		if (COption::GetOptionString("crm", "start_bp_within_bp", "N") == "Y")
		{
			$CCrmBizProc = new CCrmBizProc('COMPANY');
			if (false === $CCrmBizProc->CheckFields($arDocumentID['ID'], true))
				throw new \Bitrix\Main\SystemException($CCrmBizProc->LAST_ERROR);

			if ($res && !$CCrmBizProc->StartWorkflow($arDocumentID['ID']))
			{
				if ($useTransaction)
				{
					$DB->Rollback();
				}
				throw new \Bitrix\Main\SystemException($CCrmBizProc->LAST_ERROR);
			}
		}

		if (isset($arFields['TRACKING_SOURCE_ID']))
		{
			Crm\Tracking\UI\Details::saveEntityData(
				\CCrmOwnerType::Company,
				$arDocumentID['ID'],
				$arFields
			);
		}

		if ($res && $useTransaction)
		{
			$DB->Commit();
		}
	}

	/**
	 * @deprecated
	 * @see Crm\Integration\BizProc\Document\ValueCollection\Company
	 */
	public static function PrepareDocument(array &$arFields)
	{
	}

	public static function getDocumentName($documentId)
	{
		$arDocumentID = self::GetDocumentInfo($documentId);
		$dbRes = CCrmCompany::GetListEx([], ['=ID' => $arDocumentID['ID'], 'CHECK_PERMISSIONS' => 'N'],
			false, false, ['TITLE']
		);
		$arRes = $dbRes ? $dbRes->Fetch() : null;
		return $arRes ? $arRes['TITLE'] : '';
	}

	public static function normalizeDocumentId($documentId)
	{
		return parent::normalizeDocumentIdInternal(
			$documentId,
			CCrmOwnerType::CompanyName,
			CCrmOwnerTypeAbbr::Company
		);
	}

	public static function createAutomationTarget($documentType)
	{
		return Crm\Automation\Factory::createTarget(\CCrmOwnerType::Company);
	}
}

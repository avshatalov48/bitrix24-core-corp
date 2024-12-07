<?php

use Bitrix\Crm;

if (!CModule::IncludeModule('bizproc'))
	return;

IncludeModuleLangFile(__DIR__."/crm_document.php");

class CCrmDocumentLead extends CCrmDocument
	implements IBPWorkflowDocument
{
	static public function GetDocumentFields($documentType)
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

		return new Crm\Integration\BizProc\Document\ValueCollection\Lead(
			CCrmOwnerType::Lead,
			$documentInfo['ID']
		);
	}

	public static function getEntityFields($entityType)
	{
		\Bitrix\Main\Localization\Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/components/bitrix/crm.'.
			mb_strtolower($entityType).'.edit/component.php');

		$addressLabels = Crm\EntityAddress::getShortLabels();
		$printableFieldNameSuffix = ' ('.GetMessage('CRM_FIELD_BP_TEXT').')';

		$arResult = static::getVirtualFields() + [
			'ID' => [
				'Name' => GetMessage('CRM_FIELD_ID'),
				'Type' => 'int',
				'Filterable' => true,
				'Editable' => false,
				'Required' => false,
			],
			'TITLE' => [
				'Name' => GetMessage('CRM_FIELD_TITLE_LEAD'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => true,
			],
			'STATUS_ID' => [
				'Name' => CCrmLead::GetFieldCaption('STATUS_ID'),
				'Type' => 'select',
				'Options' => CCrmStatus::GetStatusListEx('STATUS'),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'STATUS_ID_PRINTABLE' => [
				'Name' => CCrmLead::GetFieldCaption('STATUS_ID') . $printableFieldNameSuffix,
				'Type' => 'string',
				'Filterable' => false,
				'Editable' => false,
				'Required' => false,
			],
			'STATUS_DESCRIPTION' => [
				'Name' => CCrmLead::GetFieldCaption('STATUS_DESCRIPTION'),
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
				'Name' => GetMessage('CRM_FIELD_CREATED_BY_ID'),
				'Type' => 'user',
				'Filterable' => true,
				'Editable' => false,
				'Required' => false,
			],
			'CREATED_BY_PRINTABLE' => [
				'Name' => GetMessage('CRM_FIELD_CREATED_BY_ID').$printableFieldNameSuffix,
				'Type' => 'string',
				'Filterable' => false,
				'Editable' => false,
				'Required' => false,
			],
			'MODIFY_BY_ID' => [
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_MODIFY_BY_ID'),
				'Type' => 'user',
			],
			'COMMENTS' => [
				'Name' => GetMessage('CRM_FIELD_COMMENTS'),
				'Type' => 'text',
				'ValueContentType' => 'bb',
				'Filterable' => false,
				'Editable' => true,
				'Required' => false,
			],
			"OBSERVER_IDS" => [
				"Name" => GetMessage("CRM_FIELD_OBSERVER_IDS"),
				"Type" => "user",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
				"Multiple" => true,
			],
			'NAME' => [
				'Name' => GetMessage('CRM_LEAD_FIELD_NAME'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'LAST_NAME' => [
				'Name' => GetMessage('CRM_FIELD_LAST_NAME'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'SECOND_NAME' => [
				'Name' => GetMessage('CRM_FIELD_SECOND_NAME'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'HONORIFIC' => [
				'Name' => GetMessage('CRM_FIELD_HONORIFIC'),
				'Type' => 'select',
				'Options' => CCrmStatus::GetStatusListEx('HONORIFIC'),
				'Editable' => true,
				'Required' => false,
			],
			'BIRTHDATE' => [
				'Name' => GetMessage('CRM_LEAD_EDIT_FIELD_BIRTHDATE'),
				'Type' => 'datetime',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'EMAIL' => [
				'Name' => GetMessage('CRM_FIELD_EMAIL'),
				'Type' => 'email',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'PHONE' => [
				'Name' => GetMessage('CRM_FIELD_PHONE'),
				'Type' => 'phone',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'WEB' => [
				'Name' => GetMessage('CRM_FIELD_WEB'),
				'Type' => 'web',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'IM' => [
				'Name' => GetMessage('CRM_FIELD_MESSENGER'),
				'Type' => 'im',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'COMPANY_TITLE' => [
				'Name' => GetMessage('CRM_FIELD_COMPANY_TITLE'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'POST' => [
				'Name' => GetMessage('CRM_FIELD_POST'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'FULL_ADDRESS' => [
				'Name' => GetMessage('CRM_FIELD_ADDRESS'),
				'Type' => 'text',
				'Filterable' => false,
				'Editable' => false,
				'Required' => false,
			],
			'ADDRESS' => [
				'Name' => $addressLabels['ADDRESS'],
				'Type' => 'text',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'ADDRESS_2' => [
				'Name' => $addressLabels['ADDRESS_2'],
				'Type' => 'text',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'ADDRESS_CITY' => [
				'Name' => $addressLabels['CITY'],
				'Type' => 'text',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'ADDRESS_POSTAL_CODE' => [
				'Name' => $addressLabels['POSTAL_CODE'],
				'Type' => 'text',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'ADDRESS_REGION' => array(
				'Name' => $addressLabels['REGION'],
				'Type' => 'text',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'ADDRESS_PROVINCE' => [
				'Name' => $addressLabels['PROVINCE'],
				'Type' => 'text',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'ADDRESS_COUNTRY' => [
				'Name' => $addressLabels['COUNTRY'],
				'Type' => 'text',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'SOURCE_ID' => [
				'Name' => GetMessage('CRM_FIELD_SOURCE_ID'),
				'Type' => 'select',
				'Options' => CCrmStatus::GetStatusListEx('SOURCE'),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			],
			'SOURCE_DESCRIPTION' => [
				'Name' => GetMessage('CRM_FIELD_SOURCE_DESCRIPTION'),
				'Type' => 'text',
				'Filterable' => false,
				'Editable' => true,
				'Required' => false,
			],
			"DATE_CREATE" => [
				"Name" => GetMessage("CRM_LEAD_EDIT_FIELD_DATE_CREATE"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			],
			"DATE_MODIFY" => [
				"Name" => GetMessage("CRM_LEAD_EDIT_FIELD_DATE_MODIFY"),
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
				'Name' => GetMessage('CRM_DOCUMENT_LEAD_IS_RETURN_CUSTOMER'),
				'Type' => 'bool',
				'Editable' => false,
			],
			"CONTACT_ID" => [
				"Name" => GetMessage("CRM_DOCUMENT_CRM_ENTITY_TYPE_CONTACT"),
				"Type" => "UF:crm",
				"Options" => ['CONTACT' => 'Y'],
			],
			"CONTACT_IDS" => [
				"Name" => GetMessage("CRM_DOCUMENT_FIELD_CONTACT_IDS"),
				"Type" => "UF:crm",
				"Options" => ['CONTACT' => 'Y'],
				"Multiple" => true,
			],
			"COMPANY_ID" => [
				"Name" => GetMessage("CRM_DOCUMENT_CRM_ENTITY_TYPE_COMPANY"),
				"Type" => "UF:crm",
				"Options" => ['COMPANY' => 'Y'],
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

		$ar =  CCrmFieldMulti::GetEntityTypeList();
		foreach ($ar as $typeId => $arFields)
		{
			$arResult[$typeId.'_PRINTABLE'] = array(
				'Name' => GetMessage('CRM_FIELD_MULTI_'.$typeId).$printableFieldNameSuffix,
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
		$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, 'CRM_LEAD');
		$CCrmUserType->AddBPFields($arResult, array('PRINTABLE_SUFFIX' => GetMessage("CRM_FIELD_BP_TEXT")));

		//append UTM fields
		$arResult += parent::getUtmFields();

		//append FORM fields
		$arResult += parent::getSiteFormFields(CCrmOwnerType::Lead);

		return $arResult;
	}

	/**
	 * @deprecated
	 * @see Crm\Integration\BizProc\Document\ValueCollection\Deal
	 */
	public static function PrepareDocument(array &$arFields)
	{
	}

	static public function CreateDocument($parentDocumentId, $arFields)
	{
		if(!is_array($arFields))
		{
			throw new Exception("Entity fields must be array");
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
				self::InternalizeEnumerationField('CRM_LEAD', $arFields, $key);
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

		$useTransaction = static::shouldUseTransaction();

		if ($useTransaction)
		{
			$DB->StartTransaction();
		}

		if(isset($arFields['COMMENTS']))
		{
			$arFields['COMMENTS'] = static::sanitizeCommentsValue($arFields['COMMENTS']);
		}

		$CCrmEntity = new CCrmLead(false);
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

		if (COption::GetOptionString("crm", "start_bp_within_bp", "N") == "Y")
		{
			$CCrmBizProc = new CCrmBizProc('LEAD');
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

		if (isset($arFields['TRACKING_SOURCE_ID']))
		{
			Crm\Tracking\UI\Details::saveEntityData(\CCrmOwnerType::Lead, $id, $arFields);
		}

		//region automation
		$starter = new Crm\Automation\Starter(\CCrmOwnerType::Lead, $id);
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
			throw new CBPArgumentNullException('documentId');

		if(!CCrmLead::Exists($arDocumentID['ID']))
		{
			throw new Exception(GetMessage('CRM_DOCUMENT_ELEMENT_IS_NOT_FOUND'));
		}

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
				self::InternalizeEnumerationField('CRM_LEAD', $arFields, $key);
			}
			elseif ($fieldType == "file")
			{
				$arFields[$key] = static::castFileFieldValues(
					$arDocumentID['ID'],
					\CCrmOwnerType::Lead,
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

		if(isset($arFields['COMMENTS']) && $arFields['COMMENTS'] !== '')
		{
			$arFields['COMMENTS'] = static::sanitizeCommentsValue($arFields['COMMENTS']);
		}

		//check STATUS_ID changes for automation
		$arPresentFields = [];
		if (isset($arFields['STATUS_ID']))
		{
			$dbDocumentList = CCrmLead::GetListEx(
				array(),
				array('ID' => $arDocumentID['ID'], 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID', 'STATUS_ID')
			);
			$arPresentFields = $dbDocumentList->Fetch();
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

		$updatedFields = $arFields;

		$CCrmEntity = new CCrmLead(false);
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

		if (COption::GetOptionString("crm", "start_bp_within_bp", "N") == "Y")
		{
			$CCrmBizProc = new CCrmBizProc('LEAD');
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

		if (isset($arFields['TRACKING_SOURCE_ID']))
		{
			Crm\Tracking\UI\Details::saveEntityData(
				\CCrmOwnerType::Lead,
				$arDocumentID['ID'],
				$arFields
			);
		}

		//region automation
		$starter = new Crm\Automation\Starter(\CCrmOwnerType::Lead, $arDocumentID['ID']);
		$starter->setContextToBizproc()->runOnUpdate($updatedFields, $arPresentFields);
		//endregion

		if ($res && $useTransaction)
		{
			$DB->Commit();
		}
	}

	public static function getDocumentName($documentId)
	{
		$arDocumentID = self::GetDocumentInfo($documentId);
		$caption = '';

		$dbRes = CCrmLead::GetListEx([], ['=ID' => $arDocumentID['ID'], 'CHECK_PERMISSIONS' => 'N'],
			false, false,
			['TITLE', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME']
		);
		$arRes = $dbRes ? $dbRes->Fetch() : null;

		if ($arRes)
		{
			$caption = isset($arRes['TITLE']) ? $arRes['TITLE'] : '';
			if ($caption === '')
			{
				$caption = CCrmLead::PrepareFormattedName(
					array(
						'HONORIFIC'   => isset($arRes['HONORIFIC']) ? $arRes['HONORIFIC'] : '',
						'NAME'        => isset($arRes['NAME']) ? $arRes['NAME'] : '',
						'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : '',
						'LAST_NAME'   => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : ''
					)
				);
			}
		}

		return $caption;
	}

	public static function normalizeDocumentId($documentId)
	{
		return parent::normalizeDocumentIdInternal(
			$documentId,
			CCrmOwnerType::LeadName,
			CCrmOwnerTypeAbbr::Lead
		);
	}

	public static function createAutomationTarget($documentType)
	{
		return Crm\Automation\Factory::createTarget(\CCrmOwnerType::Lead);
	}
}

<?
if (!CModule::IncludeModule('bizproc'))
	return;

IncludeModuleLangFile(dirname(__FILE__)."/crm_document.php");

class CCrmDocumentContact extends CCrmDocument
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

	public static function getEntityFields($entityType)
	{
		\Bitrix\Main\Localization\Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/components/bitrix/crm.'.
			mb_strtolower($entityType).'.edit/component.php');

		$addressLabels = Bitrix\Crm\EntityAddress::getShortLabels();
		$printableFieldNameSuffix = ' ('.GetMessage('CRM_FIELD_BP_TEXT').')';

		$arResult = array(
			'ID' => array(
				'Name' => GetMessage('CRM_FIELD_ID'),
				'Type' => 'int',
				'Filterable' => true,
				'Editable' => false,
				'Required' => false,
			),
			'NAME' => array(
				'Name' => GetMessage('CRM_FIELD_FIRST_NAME'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => true,
			),
			'LAST_NAME' => array(
				'Name' => GetMessage('CRM_FIELD_LAST_NAME'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => true,
			),
			'SECOND_NAME' => array(
				'Name' => GetMessage('CRM_FIELD_SECOND_NAME'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'HONORIFIC' => array(
				'Name' => GetMessage('CRM_FIELD_HONORIFIC'),
				'Type' => 'select',
				'Options' => CCrmStatus::GetStatusListEx('HONORIFIC'),
				'Editable' => true,
				'Required' => false,
			),
			'BIRTHDATE' => array(
				'Name' => GetMessage('CRM_FIELD_BIRTHDATE'),
				'Type' => 'datetime',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'PHOTO' => array(
				'Name' => GetMessage('CRM_FIELD_PHOTO'),
				'Type' => 'file',
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
			'POST' => array(
				'Name' => GetMessage('CRM_FIELD_POST'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'FULL_ADDRESS' => array(
				'Name' => GetMessage('CRM_FIELD_ADDRESS'),
				'Type' => 'text',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'ADDRESS' => array(
				'Name' => $addressLabels['ADDRESS'],
				'Type' => 'text',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'ADDRESS_2' => array(
				'Name' => $addressLabels['ADDRESS_2'],
				'Type' => 'text',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'ADDRESS_CITY' => array(
				'Name' => $addressLabels['CITY'],
				'Type' => 'text',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'ADDRESS_POSTAL_CODE' => array(
				'Name' => $addressLabels['POSTAL_CODE'],
				'Type' => 'text',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'ADDRESS_REGION' => array(
				'Name' => $addressLabels['REGION'],
				'Type' => 'text',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'ADDRESS_PROVINCE' => array(
				'Name' => $addressLabels['PROVINCE'],
				'Type' => 'text',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'ADDRESS_COUNTRY' => array(
				'Name' => $addressLabels['COUNTRY'],
				'Type' => 'text',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'COMMENTS' => array(
				'Name' => GetMessage('CRM_FIELD_COMMENTS'),
				'Type' => 'text',
				'Filterable' => false,
				'Editable' => true,
				'Required' => false,
			),
			'TYPE_ID' => array(
				'Name' => GetMessage('CRM_FIELD_TYPE_ID'),
				'Type' => 'select',
				'Options' => CCrmStatus::GetStatusListEx('CONTACT_TYPE'),
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
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_CREATED_BY_ID_CONTACT'),
				'Type' => 'user',
			),
			'MODIFY_BY_ID' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_MODIFY_BY_ID'),
				'Type' => 'user',
			),
			'SOURCE_ID' => array(
				'Name' => GetMessage('CRM_FIELD_SOURCE_ID'),
				'Type' => 'select',
				'Options' => CCrmStatus::GetStatusListEx('SOURCE'),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'SOURCE_DESCRIPTION' => array(
				'Name' => GetMessage('CRM_FIELD_SOURCE_DESCRIPTION'),
				'Type' => 'text',
				'Filterable' => false,
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
			"EXPORT" => array(
				"Name" => GetMessage("CRM_FIELD_EXPORT"),
				"Type" => "bool",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"COMPANY_ID" => array(
				"Name" => GetMessage("CRM_FIELD_COMPANY_ID"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"COMPANY_IDS" => array(
				"Name" => GetMessage("CRM_FIELD_COMPANY_IDS"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
				"Multiple" => true
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
			"DATE_CREATE" => array(
				"Name" => GetMessage("CRM_CONTACT_EDIT_FIELD_DATE_CREATE"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"DATE_MODIFY" => array(
				"Name" => GetMessage("CRM_CONTACT_EDIT_FIELD_DATE_MODIFY"),
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
		);

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
		$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, 'CRM_CONTACT');
		$CCrmUserType->AddBPFields($arResult, array('PRINTABLE_SUFFIX' => GetMessage("CRM_FIELD_BP_TEXT")));

		//append UTM fields
		$arResult += parent::getUtmFields();

		//append FORM fields
		$arResult += parent::getSiteFormFields(CCrmOwnerType::Contact);

		return $arResult;
	}

	static public function PrepareDocument(array &$arFields)
	{
		$arFields['FULL_ADDRESS'] = Bitrix\Crm\Format\LeadAddressFormatter::format(
			$arFields,
			array('SEPARATOR' => Bitrix\Crm\Format\AddressSeparator::Comma)
		);
		$arFields['COMPANY_IDS'] = \Bitrix\Crm\Binding\ContactCompanyTable::getContactCompanyIDs($arFields['ID']);
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
			if ($arDocumentFields[$key]["Type"] == "user")
			{
				$ar = array();
				foreach ($arFields[$key] as $v1)
				{
					if (mb_substr($v1, 0, mb_strlen("user_")) == "user_")
					{
						$ar[] = mb_substr($v1, mb_strlen("user_"));
					}
					else
					{
						$a1 = self::GetUsersFromUserGroup($v1, "CONTACT_0");
						foreach ($a1 as $a11)
							$ar[] = $a11;
					}
				}

				$arFields[$key] = $ar;
			}
			elseif ($fieldType == "select" && mb_substr($key, 0, 3) == "UF_")
			{
				self::InternalizeEnumerationField('CRM_CONTACT', $arFields, $key);
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

		if(isset($arFields['COMMENTS']))
		{
			$arFields['COMMENTS'] = static::sanitizeCommentsValue($arFields['COMMENTS']);
		}

		$useTransaction = static::shouldUseTransaction();

		if ($useTransaction)
		{
			$DB->StartTransaction();
		}

		$CCrmEntity = new CCrmContact(false);
		$ID = $CCrmEntity->Add(
			$arFields,
			true,
			array('REGISTER_SONET_EVENT' => true, 'CURRENT_USER' => static::getSystemUserId())
		);

		if ($ID <= 0)
		{
			if ($useTransaction)
			{
				$DB->Rollback();
			}
			throw new Exception($CCrmEntity->LAST_ERROR);
		}

		//region Try to create requisite
		if((isset($arFields['ADDRESS']) && $arFields['ADDRESS'] !== '') ||
			(isset($arFields['ADDRESS_2']) && $arFields['ADDRESS_2'] !== '') ||
			(isset($arFields['ADDRESS_CITY']) && $arFields['ADDRESS_CITY'] !== ''))
		{
			$presetID = \Bitrix\Crm\EntityRequisite::getDefaultPresetId(CCrmOwnerType::Contact);
			if($presetID > 0)
			{
				$converter = new \Bitrix\Crm\Requisite\AddressRequisiteConverter(CCrmOwnerType::Contact, $presetID, false);
				$converter->processEntity($ID);
			}
		}
		//endregion

		if (COption::GetOptionString("crm", "start_bp_within_bp", "N") == "Y")
		{
			$CCrmBizProc = new CCrmBizProc(CCrmOwnerType::ContactName);
			if (false === $CCrmBizProc->CheckFields(false, true))
				throw new Exception($CCrmBizProc->LAST_ERROR);

			if (!$CCrmBizProc->StartWorkflow($ID))
			{
				if ($useTransaction)
				{
					$DB->Rollback();
				}
				throw new Exception($CCrmBizProc->LAST_ERROR);
			}
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


		if(!CCrmContact::Exists($arDocumentID['ID']))
		{
			throw new Exception(GetMessage('CRM_DOCUMENT_ELEMENT_IS_NOT_FOUND'));
		}

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
				$ar = array();
				foreach ($arFields[$key] as $v1)
				{
					if (mb_substr($v1, 0, mb_strlen("user_")) == "user_")
					{
						$ar[] = mb_substr($v1, mb_strlen("user_"));
					}
					else
					{
						$a1 = self::GetUsersFromUserGroup($v1, $documentId);
						foreach ($a1 as $a11)
							$ar[] = $a11;
					}
				}

				$arFields[$key] = $ar;
			}
			elseif ($fieldType == "select" && mb_substr($key, 0, 3) == "UF_")
			{
				self::InternalizeEnumerationField('CRM_CONTACT', $arFields, $key);
			}
			elseif ($fieldType == "file")
			{
				$arFileOptions = array('ENABLE_ID' => true);
				foreach ($arFields[$key] as &$value)
				{
					//Issue #40380. Secure URLs and file IDs are allowed.
					$file = false;
					if (\CCrmFileProxy::TryResolveFile($value, $file, $arFileOptions))
					{
						global $USER_FIELD_MANAGER;
						if ($USER_FIELD_MANAGER instanceof \CUserTypeManager)
						{
							$prevValue = $USER_FIELD_MANAGER->GetUserFieldValue(
								\CCrmOwnerType::ResolveUserFieldEntityID(\CCrmOwnerType::Contact),
								$key,
								$arDocumentID['ID']
							);
							if ($prevValue)
							{
								$file['old_id'] = $prevValue;
							}
						}
					}
					$value = $file;
				}
				unset($value, $prevValue);
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

		$useTransaction = static::shouldUseTransaction();

		if ($useTransaction)
		{
			$DB->StartTransaction();
		}

		if ($modifiedById > 0)
		{
			$arFields['MODIFY_BY_ID'] = $modifiedById;
		}

		$CCrmEntity = new CCrmContact(false);
		$res = $CCrmEntity->Update(
			$arDocumentID['ID'],
			$arFields,
			true,
			true,
			[
				'DISABLE_USER_FIELD_CHECK' => true,
				'REGISTER_SONET_EVENT' => true,
				'CURRENT_USER' => static::getSystemUserId()
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
			$CCrmBizProc = new CCrmBizProc('CONTACT');
			if (false === $CCrmBizProc->CheckFields($arDocumentID['ID'], true))
				throw new Exception($CCrmBizProc->LAST_ERROR);

			if ($res && !$CCrmBizProc->StartWorkflow($arDocumentID['ID']))
			{
				if ($useTransaction)
				{
					$DB->Rollback();
				}
				throw new Exception($CCrmBizProc->LAST_ERROR);
			}
		}

		if ($res && $useTransaction)
		{
			$DB->Commit();
		}
	}

	public static function getDocumentName($documentId)
	{
		$arDocumentID = self::GetDocumentInfo($documentId);
		$caption = '';

		$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $arDocumentID['ID'], 'CHECK_PERMISSIONS' => 'N'),
			false, false,
			['HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME']
		);
		$arRes = $dbRes ? $dbRes->Fetch() : null;

		if($arRes)
		{
			$caption = CCrmContact::PrepareFormattedName([
				'HONORIFIC' => isset($arRes['HONORIFIC']) ? $arRes['HONORIFIC'] : '',
				'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : '',
				'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : '',
				'LAST_NAME' => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : ''
			]);
		}

		return $caption;
	}

	public static function normalizeDocumentId($documentId)
	{
		return parent::normalizeDocumentIdInternal(
			$documentId,
			CCrmOwnerType::ContactName,
			CCrmOwnerTypeAbbr::Contact
		);
	}
}

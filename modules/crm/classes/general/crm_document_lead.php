<?

use Bitrix\Crm;

if (!CModule::IncludeModule('bizproc'))
	return;

IncludeModuleLangFile(dirname(__FILE__)."/crm_document.php");

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

	public static function getEntityFields($entityType)
	{
		\Bitrix\Main\Localization\Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/components/bitrix/crm.'.
			strtolower($entityType).'.edit/component.php');

		$addressLabels = Crm\EntityAddress::getShortLabels();
		$printableFieldNameSuffix = ' ('.GetMessage('CRM_FIELD_BP_TEXT').')';

		$arResult = array(
			'ID' => array(
				'Name' => GetMessage('CRM_FIELD_ID'),
				'Type' => 'int',
				'Filterable' => true,
				'Editable' => false,
				'Required' => false,
			),
			'TITLE' => array(
				'Name' => GetMessage('CRM_FIELD_TITLE_LEAD'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => true,
			),
			'STATUS_ID' => array(
				'Name' => GetMessage('CRM_FIELD_STATUS_ID'),
				'Type' => 'select',
				'Options' => CCrmStatus::GetStatusListEx('STATUS'),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'STATUS_ID_PRINTABLE' => array(
				'Name' => GetMessage('CRM_FIELD_STATUS_ID').$printableFieldNameSuffix,
				'Type' => 'string',
				'Filterable' => false,
				'Editable' => false,
				'Required' => false,
			),
			'STATUS_DESCRIPTION' => array(
				'Name' => GetMessage('CRM_FIELD_STATUS_DESCRIPTION'),
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
			'OPPORTUNITY' => array(
				'Name' => GetMessage('CRM_FIELD_OPPORTUNITY'),
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
				'Name' => GetMessage('CRM_FIELD_CREATED_BY_ID'),
				'Type' => 'user',
				'Filterable' => true,
				'Editable' => false,
				'Required' => false,
			),
			'CREATED_BY_PRINTABLE' => array(
				'Name' => GetMessage('CRM_FIELD_CREATED_BY_ID').$printableFieldNameSuffix,
				'Type' => 'string',
				'Filterable' => false,
				'Editable' => false,
				'Required' => false,
			),
			'MODIFY_BY_ID' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_MODIFY_BY_ID'),
				'Type' => 'user',
			),
			'COMMENTS' => array(
				'Name' => GetMessage('CRM_FIELD_COMMENTS'),
				'Type' => 'text',
				'Filterable' => false,
				'Editable' => true,
				'Required' => false,
			),
			'NAME' => array(
				'Name' => GetMessage('CRM_LEAD_FIELD_NAME'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'LAST_NAME' => array(
				'Name' => GetMessage('CRM_FIELD_LAST_NAME'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'SECOND_NAME' => array(
				'Name' => GetMessage('CRM_FIELD_SECOND_NAME'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'BIRTHDATE' => array(
				'Name' => GetMessage('CRM_LEAD_EDIT_FIELD_BIRTHDATE'),
				'Type' => 'datetime',
				'Filterable' => true,
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
			'COMPANY_TITLE' => array(
				'Name' => GetMessage('CRM_FIELD_COMPANY_TITLE'),
				'Type' => 'string',
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
				'Filterable' => false,
				'Editable' => false,
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
			"DATE_CREATE" => array(
				"Name" => GetMessage("CRM_LEAD_EDIT_FIELD_DATE_CREATE"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"DATE_MODIFY" => array(
				"Name" => GetMessage("CRM_LEAD_EDIT_FIELD_DATE_MODIFY"),
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
			'IS_RETURN_CUSTOMER' => array(
				'Name' => GetMessage('CRM_DOCUMENT_LEAD_IS_RETURN_CUSTOMER'),
				'Type' => 'bool',
				'Editable' => false,
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
		$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, 'CRM_LEAD');
		$CCrmUserType->AddBPFields($arResult, array('PRINTABLE_SUFFIX' => GetMessage("CRM_FIELD_BP_TEXT")));

		//append UTM fields
		$arResult += parent::getUtmFields();

		//append FORM fields
		$arResult += parent::getSiteFormFields(CCrmOwnerType::Lead);

		return $arResult;
	}

	static public function PrepareDocument(array &$arFields)
	{
		$stuses = CCrmStatus::GetStatusList('STATUS');
		$statusID = isset($arFields['STATUS_ID']) ? $arFields['STATUS_ID'] : '';
		$arFields['STATUS_ID_PRINTABLE'] = $statusID !== '' && isset($stuses[$statusID]) ? $stuses[$statusID] : '';

		if (CCrmLead::ResolveCustomerType($arFields) === Crm\CustomerType::RETURNING)
		{
			$customerFields = CCrmLead::getCustomerFields();
			if ($arFields['CONTACT_ID'] > 0)
			{
				if ($contact = CCrmContact::GetByID($arFields['CONTACT_ID'], false))
				{
					foreach ($customerFields as $customerField)
					{
						if (array_key_exists($customerField, $arFields) && !empty($contact[$customerField]))
						{
							$arFields[$customerField] = $contact[$customerField];
						}
					}
				}
			}
			if ($arFields['COMPANY_ID'] > 0 && empty($arFields['COMPANY_TITLE']))
			{
				$dbRes = \CCrmCompany::GetListEx([], ['=ID' => $arFields['COMPANY_ID'], 'CHECK_PERMISSIONS' => 'N'],
					false, false, ['TITLE']
				);
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				$arFields['COMPANY_TITLE'] = $arRes ? $arRes['TITLE'] : '';
			}
		}

		if ($arFields['COMPANY_ID'] <= 0)
		{
			//set empty value instead "0"
			$arFields['COMPANY_ID'] = null;
		}

		if ($arFields['CONTACT_ID'] <= 0)
		{
			//set empty value instead "0"
			$arFields['CONTACT_ID'] = null;
		}

		$arFields['FULL_ADDRESS'] = Crm\Format\LeadAddressFormatter::format(
			$arFields,
			array('SEPARATOR' => Crm\Format\AddressSeparator::Comma)
		);
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
				CCrmDocument::PrepareEntityMultiFields($arFields, strtoupper($fieldType));
				continue;
			}

			$arFields[$key] = (is_array($arFields[$key]) && !CBPHelper::IsAssociativeArray($arFields[$key])) ? $arFields[$key] : array($arFields[$key]);
			if ($fieldType == "user")
			{
				$ar = array();
				foreach ($arFields[$key] as $v1)
				{
					if (substr($v1, 0, strlen("user_")) == "user_")
					{
						$ar[] = substr($v1, strlen("user_"));
					}
					else
					{
						$a1 = self::GetUsersFromUserGroup($v1, "LEAD_0");
						foreach ($a1 as $a11)
							$ar[] = $a11;
					}
				}

				$arFields[$key] = $ar;
			}
			elseif ($fieldType == "select" && substr($key, 0, 3) == "UF_")
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
			array('REGISTER_SONET_EVENT' => true, 'CURRENT_USER' => static::getSystemUserId())
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
				throw new Exception($CCrmBizProc->LAST_ERROR);

			if ($id && $id > 0 && !$CCrmBizProc->StartWorkflow($id))
			{
				if ($useTransaction)
				{
					$DB->Rollback();
				}
				throw new Exception($CCrmBizProc->LAST_ERROR);
			}
		}

		//Region automation
		Crm\Automation\Factory::runOnAdd(\CCrmOwnerType::Lead, $id);
		//End region

		if ($id && $id > 0 && $useTransaction)
		{
			$DB->Commit();
		}

		return $id;
	}

	public static function UpdateDocument($documentId, $arFields, $modifiedById = null)
	{
		global $DB;

		$arDocumentID = self::GetDocumentInfo($documentId);
		if (empty($arDocumentID))
			throw new CBPArgumentNullException('documentId');

		if(!CCrmLead::Exists($arDocumentID['ID']))
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
				CCrmDocument::PrepareEntityMultiFields($arFields, strtoupper($fieldType));
				continue;
			}

			$arFields[$key] = (is_array($arFields[$key]) && !CBPHelper::IsAssociativeArray($arFields[$key])) ? $arFields[$key] : array($arFields[$key]);
			if ($fieldType == "user")
			{
				$ar = array();
				foreach ($arFields[$key] as $v1)
				{
					if (substr($v1, 0, strlen("user_")) == "user_")
					{
						$ar[] = substr($v1, strlen("user_"));
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
			elseif ($fieldType == "select" && substr($key, 0, 3) == "UF_")
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

		if(isset($arFields['COMMENTS']) && $arFields['COMMENTS'] !== '')
		{
			$arFields['COMMENTS'] = static::sanitizeCommentsValue($arFields['COMMENTS']);
		}

		//check STATUS_ID changes
		$statusChanged = false;
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
			if ($arPresentFields['STATUS_ID'] != $arFields['STATUS_ID'])
				$statusChanged = true;
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

		$CCrmEntity = new CCrmLead(false);
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
			$CCrmBizProc = new CCrmBizProc('LEAD');
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

		//Region automation
		if ($statusChanged)
		{
			Crm\Automation\Factory::runOnStatusChanged(\CCrmOwnerType::Lead, $arDocumentID['ID']);
		}
		//End region

		if ($res && $useTransaction)
		{
			$DB->Commit();
		}
	}

	public function getDocumentName($documentId)
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

<?
IncludeModuleLangFile(__FILE__);

class CCRMLeadRest
{
	private static $bReturnObject = false;
	private static $authHash = null;
	private static $sources = null;

	/* public section */

	public static function CreateAuthHash()
	{
		global $USER, $APPLICATION;
		self::$authHash = $USER->AddHitAuthHash($APPLICATION->GetCurPage());
	}

	public static function CheckAuthHash($arData)
	{
		global $USER;

		if ($arData['AUTH'] <> '')
		{
			return $USER->LoginHitByHash($arData['AUTH']);
		}

		return false;
	}

	protected static function ResolveStatusID(array $statuses, $name)
	{
		foreach($statuses as $ID => $fields)
		{
			if(isset($fields['NAME']) && $name === $fields['NAME'])
			{
				return $ID;
			}
		}

		return '';
	}

	public static function AddLead($arData, $CCrmLead)
	{
		global $DB, $USER_FIELD_MANAGER;

		$arData['CURRENCY_ID'] = trim($arData['CURRENCY_ID']);
		if ($arData['CURRENCY_ID'] == '')
			$arData['CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();

		$arFields = [
			'TITLE' => trim($arData['TITLE']),
			'COMPANY_TITLE' => trim($arData['COMPANY_TITLE']),
			'NAME' => trim($arData['NAME']),
			'LAST_NAME' => trim($arData['LAST_NAME']),
			'SECOND_NAME' => trim($arData['SECOND_NAME']),
			'POST' => trim($arData['POST']),
			'ADDRESS' => trim($arData['ADDRESS']),
			'COMMENTS' => trim($arData['COMMENTS']),
			'SOURCE_DESCRIPTION' => trim($arData['SOURCE_DESCRIPTION']),
			'STATUS_DESCRIPTION' => trim($arData['STATUS_DESCRIPTION']),
			'OPPORTUNITY' => trim($arData['OPPORTUNITY']),
			'CURRENCY_ID' => trim($arData['CURRENCY_ID']),
			'ASSIGNED_BY_ID' => (int)(is_array($arData['ASSIGNED_BY_ID']) ? $arData['ASSIGNED_BY_ID'][0] : $arData['ASSIGNED_BY_ID']),
			'OPENED' => \Bitrix\Crm\Settings\LeadSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N',
		];

		if (isset($arData['BIRTHDATE']))
		{
			$date = ConvertTimeStamp(MakeTimeStamp(trim($arData['BIRTHDATE'])), 'SHORT', SITE_ID);
			if($date !== false)
			{
				$arFields['BIRTHDATE'] = $date;
			}
		}

		$arData['SOURCE_ID'] = trim($arData['SOURCE_ID']);
		$arData['STATUS_ID'] = trim($arData['STATUS_ID']);

		if ($arData['STATUS_ID'] <> '')
			$arFields['STATUS_ID'] = $arData['STATUS_ID'];
		if ($arData['SOURCE_ID'] <> '')
			$arFields['SOURCE_ID'] = $arData['SOURCE_ID'];

		if(isset($arFields['SOURCE_ID']))
		{
			if(self::$sources === null)
			{
				self::$sources = CCrmStatus::GetStatus('SOURCE');
			}

			if(!isset(self::$sources[$arFields['SOURCE_ID']]))
			{
				//Crutch: Try to fix form bug. If we get source name instead of spurce ID.
				$sourceID = self::ResolveStatusID(self::$sources, $arFields['SOURCE_ID']);
				if($sourceID !== '')
				{
					$arFields['SOURCE_ID'] = $sourceID;
				}
				else
				{
					unset($arFields['SOURCE_ID']);
				}
			}
		}

		$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmLead::$sUFEntityID);
		$arFields = array_merge($arFields, $CCrmUserType->PrepareExternalFormFields($arData, ','));
		global $USER_FIELD_MANAGER;
		$USER_FIELD_MANAGER->EditFormAddFields(CCrmLead::USER_FIELD_ENTITY_ID, $arFields, [
			'FORM' => $arFields
		]);
		$arFields['FM'] = CCrmFieldMulti::PrepareFields($arData);

		$DB->StartTransaction();

		$ID = $CCrmLead->Add($arFields);


		if ($ID === false)
		{
			$DB->Rollback();
			if (!empty($arFields['RESULT_MESSAGE']))
				$sErrorMessage = $arFields['RESULT_MESSAGE'];
			else
				$sErrorMessage = GetMessage('UNKNOWN_ERROR');

			$res =  array('error' => 400, 'error_message' => strip_tags(nl2br($sErrorMessage)));
		}
		else
		{
			$DB->Commit();

			// Ignore all BizProc errors
			try
			{
				$arErrors = array();
				CCrmBizProcHelper::AutoStartWorkflows(
					CCrmOwnerType::Lead,
					$ID,
					CCrmBizProcEventType::Create,
					$arErrors
				);

				//Region automation
				$starter = new \Bitrix\Crm\Automation\Starter(\CCrmOwnerType::Lead, $ID);
				$starter->setContextToRest()->runOnAdd();
				//End region
			}
			catch(Exception $e)
			{
			}

			$res = array('error' => 201, 'ID' => $ID, 'error_message' => GetMessage('CRM_REST_OK'));
		}

		return self::_out($res);
	}

	public static function AddLeadBundle($arLeads, $CCrmLead)
	{
		if (is_array($arLeads))
		{
			$res = array();
			self::$bReturnObject = true;
			foreach ($arLeads as $arLeadData)
			{
				$res[] = CCrmLeadRest::AddLead($arLeadData, $CCrmLead, true);
			}
			self::$bReturnObject = false;
			return self::_out(array('RESULTS' => $res));
		}
		else
		{
			return self::_out(array('error' => 400, 'error_message' => GetMessage('CRM_REST_ERROR_BAD_REQUEST')));
		}
	}

	public static function GetFields()
	{
		$fields = array();
		$fields[] = array('ID' => 'TITLE', 'NAME' => GetMessage('CRM_FIELD_TITLE'), 'TYPE' => 'string', 'REQUIRED' => true);
		$fields[] = array('ID' => 'NAME', 'NAME' => GetMessage('CRM_FIELD_REST_NAME'), 'TYPE' => 'string', 'REQUIRED' => false);
		$fields[] = array('ID' => 'LAST_NAME', 'NAME' => GetMessage('CRM_FIELD_LAST_NAME'), 'TYPE' => 'string', 'REQUIRED' => false);
		$fields[] = array('ID' => 'SECOND_NAME', 'NAME' => GetMessage('CRM_FIELD_SECOND_NAME'), 'TYPE' => 'string', 'REQUIRED' => false);

		$ar = CCrmFieldMulti::GetEntityComplexList();
		foreach($ar as $fieldId => $fieldName)
		{
			$fields[] = array('ID' => $fieldId, 'NAME' => $fieldName, 'TYPE' => 'string', 'REQUIRED' => false);
		}

		$fields[] = array('ID' => 'COMPANY_TITLE', 'NAME' => GetMessage('CRM_FIELD_COMPANY_TITLE'), 'TYPE' => 'string', 'REQUIRED' => false);
		$fields[] = array('ID' => 'POST', 'NAME' => GetMessage('CRM_FIELD_POST'), 'TYPE' => 'string', 'REQUIRED' => false);
		$fields[] = array('ID' => 'ADDRESS', 'NAME' => GetMessage('CRM_FIELD_ADDRESS'), 'TYPE' => 'string', 'REQUIRED' => false);
		$fields[] = array('ID' => 'COMMENTS', 'NAME' => GetMessage('CRM_FIELD_COMMENTS'), 'TYPE' => 'string', 'REQUIRED' => false);
		$fields[] = array('ID' => 'STATUS_ID', 'NAME' => GetMessage('CRM_FIELD_STATUS_ID'), 'TYPE' => 'enum', 'VALUES' => self::_GetStatusList(), 'REQUIRED' => false);
		$fields[] = array('ID' => 'CURRENCY_ID', 'NAME' => GetMessage('CRM_FIELD_CURRENCY_ID'), 'TYPE' => 'enum', 'VALUES' => self::_GetCurrencyList(), 'REQUIRED' => false);
		$fields[] = array('ID' => 'SOURCE_ID', 'NAME' => GetMessage('CRM_FIELD_SOURCE_ID'), 'TYPE' => 'enum', 'VALUES' => self::_GetSourceList(), 'REQUIRED' => false);
		$fields[] = array('ID' => 'OPPORTUNITY', 'NAME' => GetMessage('CRM_FIELD_OPPORTUNITY'), 'TYPE' => 'double', 'REQUIRED' => false);
		$fields[] = array('ID' => 'STATUS_DESCRIPTION', 'NAME' => GetMessage('CRM_FIELD_STATUS_DESCRIPTION'), 'TYPE' => 'string', 'REQUIRED' => false);
		$fields[] = array('ID' => 'SOURCE_DESCRIPTION', 'NAME' => GetMessage('CRM_FIELD_SOURCE_DESCRIPTION'), 'TYPE' => 'string', 'REQUIRED' => false);
		$fields[] = array('ID' => 'BIRTHDATE', 'NAME' => GetMessage('CRM_FIELD_BIRTHDATE'), 'TYPE' => 'date', 'REQUIRED' => false);

		$CCrmUserType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], CCrmLead::$sUFEntityID);
		$CCrmUserType->AddRestServiceFields($fields);

		return self::_out(array('error' => 201, 'FIELDS' => $fields));
	}

	/* private section */

	private static function _GetStatusList()
	{
		$ar = CCrmStatus::GetStatusList('STATUS');
		$list = array();

		foreach ($ar as $key => $value)
		{
			$list[] = array('ID' => $key, 'NAME' => $value);
		}

		return $list;
	}

	private static function _GetCurrencyList()
	{
		$ar = CCrmCurrencyHelper::PrepareListItems();
		$list = array();

		foreach ($ar as $key => $value)
		{
			$list[] = array('ID' => $key, 'NAME' => $value);
		}

		return $list;
	}

	private static function _GetSourceList()
	{
		$ar = CCrmStatus::GetStatusListEx('SOURCE');
		$list = array();

		foreach ($ar as $key => $value)
		{
			$list[] = array('ID' => $key, 'NAME' => $value);
		}

		return $list;
	}

	private static function _out($data)
	{
		global $APPLICATION;

		if (self::$authHash)
		{
			$data['AUTH'] = self::$authHash;
		}

		return self::$bReturnObject ? $data : CUtil::PhpToJsObject($data);
	}
}
?>

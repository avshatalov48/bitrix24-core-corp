<?
IncludeModuleLangFile(__FILE__);

class CAllIntranetSharepoint
{
	public static $bUpdateInProgress = false;

	protected static $arTypesList = array();
	protected static $arTypesCreateList = array();
	protected static $arUsersCache = array();

	private static $lists_queue = null;

	/* public section */

	public static function GetList($arSort, $arFilter)
	{
		global $DB;

		$query = 'SELECT * FROM b_intranet_sharepoint';
	}

	public static function GetByID($ID, $bFull = false)
	{
		global $DB;

		if ($res = self::_GetWhere($ID))
		{
			$res = $DB->Query('SELECT * FROM b_intranet_sharepoint WHERE '.$res);

			if ($bFull)
			{
				if ($arRes = $res->Fetch())
				{
					$res = $DB->Query('SELECT * FROM b_intranet_sharepoint_field WHERE IBLOCK_ID=\''.$arRes['IBLOCK_ID'].'\'');
					$arRes['FIELDS'] = array();
					while ($arField = $res->Fetch())
						$arRes['FIELDS'][] = $arField;

					$res = new CDBResult();
					$res->InitFromArray(array($arRes));
				}
			}
		}
		else
		{
			$res = new CDBResult();
		}

		return $res;
	}

	public static function Delete($ID)
	{
		if ($where = self::_GetWhere($ID))
		{
			self::ClearListFields($ID);

			$query = "DELETE FROM b_intranet_sharepoint WHERE ".$where;
			$res = $GLOBALS['DB']->Query($query);

			return $res->AffectedRowsCount();
		}
	}

	public static function Add($arFields)
	{
		global $DB;

		if (self::CheckFields('ADD', $arFields))
		{
			$arInsert = array(
				'IBLOCK_ID' => $DB->ForSQL($arFields['IBLOCK_ID']),
				'SP_LIST_ID' => $DB->ForSQL($arFields['SP_LIST_ID']),
				'SP_URL' => $DB->ForSQL($arFields['SP_URL']),
				'SP_AUTH_USER' => $DB->ForSQL($arFields['SP_AUTH_USER']),
				'SP_AUTH_PASS' => $DB->ForSQL($arFields['SP_AUTH_PASS']),

				'SYNC_DATE' => $DB->ForSQL($arFields['SYNC_DATE']),
				'SYNC_PERIOD' => intval($arFields['SYNC_PERIOD']),
				'SYNC_ERRORS' => intval($arFields['SYNC_ERRORS']),

				'SYNC_LAST_TOKEN' => $DB->ForSQL($arFields['SYNC_LAST_TOKEN']),
				'SYNC_PAGING' => $DB->ForSQL($arFields['SYNC_PAGING']),

				'HANDLER_MODULE' => $DB->ForSQL($arFields['HANDLER_MODULE']),
				'HANDLER_CLASS' => $DB->ForSQL($arFields['HANDLER_CLASS']),

				'PRIORITY' => $DB->ForSQL($arFields['PRIORITY']),
			);

			//$DB->StartTransaction();
			$query = 'INSERT INTO b_intranet_sharepoint ('.implode(', ', array_keys($arInsert)).') VALUES (\''.implode('\', \'', $arInsert).'\')';

			if ($DB->Query($query))
			{
				if (is_array($arFields['FIELDS']))
				{
					self::SetListFields($arFields['IBLOCK_ID'], $arFields['FIELDS'], $arFields['LIST_DATA']);
					//$DB->Commit();
				}

				self::_CheckVersionProperty($arFields['IBLOCK_ID']);
			}


			return true;
		}

		return false;
	}

	public static function Update($ID, $arFields)
	{
		global $DB;
		if (self::CheckFields('UPDATE', $arFields))
		{
			if ($where = self::_GetWhere($ID))
			{
				$FIELDS = null;
				if (is_array($arFields['FIELDS']))
				{
					$FIELDS = $arFields['FIELDS'];
					$DATA = $arFields['LIST_DATA'];
					unset($arFields['FIELDS']);
					unset($arFields['LIST_DATA']);
				}

				$strUpdate = $DB->PrepareUpdate("b_intranet_sharepoint", $arFields);

				$strSql = "UPDATE b_intranet_sharepoint SET ".$strUpdate." WHERE ".$where;

				$res = $DB->Query($strSql);

				if ($res && null != $FIELDS)
					self::SetListFields($ID, $FIELDS, $DATA);

				self::_CheckVersionProperty($ID);

				return $res;
			}
		}

		return false;
	}

	protected static function _CheckVersionProperty($IBLOCK_ID)
	{
		$dbRes = CIBlockProperty::GetByID('OWSHIDDENVERSION', $IBLOCK_ID);
		if (!$arRes = $dbRes->Fetch())
		{
			$arPropFields = array(
				'IBLOCK_ID' => $IBLOCK_ID,
				'TYPE' => 'N',
				'USER_TYPE' => '',
				'CODE' => 'OWSHIDDENVERSION',
				'NAME' => 'Sharepoint version',
				'MULTIPLE' => 'N'
			);

			CIntranetSharepoint::_CreateProperty($arPropFields);
		}
	}

	public static function SetListFields($IBLOCK_ID, $arFields, $arList = null)
	{
		global $DB;

		$IBLOCK_ID = intval($IBLOCK_ID);
		CIntranetSharepoint::ClearListFields($IBLOCK_ID);

		foreach ($arFields as $sp_fld => $property)
		{
			if (!$property) continue;

			list($sp_fld_name, $sp_fld_type) = explode(':', $sp_fld, 2);

			$pos = 0;
			if (strlen($property) == 1 || (false !== ($pos = strstr($property, ':'))))
			{
				$user_type = '';

				if ($pos > 0)
					list($property, $user_type) = explode(':', $property, 2);

				$arPropFields = array(
					'IBLOCK_ID' => $IBLOCK_ID,
					'TYPE' => $property,
					'USER_TYPE' => $user_type,
					'CODE' => $sp_fld_name,
					'NAME' => $sp_fld_name,
					'MULTIPLE' => $property == 'F' ? 'Y' : 'N',
				);

				if (is_array($arList))
				{
					foreach ($arList as $list_fld)
					{
						if ($list_fld['Name'] == $sp_fld_name)
						{
							$arPropFields['NAME'] = $list_fld['DisplayName'];

							if ($property == 'L')
							{
								if ($list_fld['Type'] == 'MultiChoice')
								{
									$arPropFields['MULTIPLE'] = 'Y';
									$arPropFields['LIST_TYPE'] = 'C';
								}
								else
								{
									$arPropFields['LIST_TYPE'] =
										$list_fld['Format'] == 'RadioButtons' ? 'C' : 'L';
								}

								$arPropFields['ENUM'] = $list_fld['CHOICE'];
								$arPropFields['ENUM_DEFAULT'] = $list_fld['DEFAULT'];
							}

							break;
						}
					}
				}

				$property = 'PROPERTY_'.CIntranetSharepoint::_CreateProperty($arPropFields);
			}

			$arInsert = array(
				'IBLOCK_ID' => $DB->ForSQL($IBLOCK_ID),
				'FIELD_ID' => $DB->ForSQL($property),
				'SP_FIELD' => $DB->ForSQL($sp_fld_name),
				'SP_FIELD_TYPE' => $DB->ForSQL($sp_fld_type),
			);

			$query = 'INSERT INTO b_intranet_sharepoint_field ('.implode(', ', array_keys($arInsert)).') VALUES (\''.implode('\', \'', $arInsert).'\')';

			$DB->Query($query);

			$events = GetModuleEvents("intranet", "OnSharepointCreateProperty");
			while ($arEvent = $events->Fetch())
			{
				ExecuteModuleEventEx($arEvent, array(array(
					'IBLOCK_ID' => $IBLOCK_ID,
					'FIELD_ID' => $property,
					'SP_FIELD' => $sp_fld_name,
				)));
			}
		}
	}

	public static function ClearListFields($ID)
	{
		global $DB;

		if ($where = self::_GetWhere($ID))
		{
			$res = $DB->Query('DELETE FROM b_intranet_sharepoint_field WHERE '.$where);

			return $res->AffectedRowsCount();
		}

		return false;
	}

	public static function ClearSyncData($ID)
	{
		return self::Update($ID, array(
			'SYNC_DATE' => '',
			'SYNC_ERRORS' => 0,
			'SYNC_LAST_TOKEN' => '',
			'SYNC_PAGING' => '',
		));
	}

	public static function GetTypesCreate()
	{
		if (!self::$arTypesCreateList)
		{
			self::$arTypesCreateList = array(
				"S" => GetMessage("SP_LIST_FIELD_S"),
				"N" => GetMessage("SP_LIST_FIELD_N"),
				"L" => GetMessage("SP_LIST_FIELD_L"),
				"F" => GetMessage("SP_LIST_FIELD_F"),
				"G" => GetMessage("SP_LIST_FIELD_G"),
				"E" => GetMessage("SP_LIST_FIELD_E"),
			);

			//User types
			foreach(CIBlockProperty::GetUserType() as  $ar)
			{
				if(array_key_exists("GetPublicEditHTML", $ar))
					self::$arTypesCreateList[$ar["PROPERTY_TYPE"].":".$ar["USER_TYPE"]] = $ar["DESCRIPTION"];
			}
		}

		return self::$arTypesCreateList;
	}

	public static function GetTypes($IBLOCK_ID)
	{
		if (!self::$arTypesList[$IBLOCK_ID])
		{
			self::$arTypesList[$IBLOCK_ID] = array(
				//Element fields
				"NAME" => GetMessage("SP_LIST_FIELD_NAME"),
				"SORT" => GetMessage("SP_LIST_FIELD_SORT"),
				"ACTIVE_FROM" => GetMessage("SP_LIST_FIELD_ACTIVE_FROM"),
				"ACTIVE_TO" => GetMessage("SP_LIST_FIELD_ACTIVE_TO"),
				"PREVIEW_PICTURE" => GetMessage("SP_LIST_FIELD_PREVIEW_PICTURE"),
				"PREVIEW_TEXT" => GetMessage("SP_LIST_FIELD_PREVIEW_TEXT"),
				"DETAIL_PICTURE" => GetMessage("SP_LIST_FIELD_DETAIL_PICTURE"),
				"DETAIL_TEXT" => GetMessage("SP_LIST_FIELD_DETAIL_TEXT"),
				"DATE_CREATE" => GetMessage("SP_LIST_FIELD_DATE_CREATE"),
				"CREATED_BY" => GetMessage("SP_LIST_FIELD_CREATED_BY"),
				"TIMESTAMP_X" => GetMessage("SP_LIST_FIELD_TIMESTAMP_X"),
				"MODIFIED_BY" => GetMessage("SP_LIST_FIELD_MODIFIED_BY"),
			);

			$dbFields = CIBlockProperty::GetList(array('SORT' => 'ASC', 'NAME' => 'ASC'), array('IBLOCK_ID' => $IBLOCK_ID, 'CHECK_PERMISSIONS' => 'N'));
			while ($arField = $dbFields->Fetch())
			{
				self::$arTypesList[$IBLOCK_ID]['PROPERTY_'.$arField['ID']] = $arField['NAME'];
			}
		}

		return self::$arTypesList[$IBLOCK_ID];
	}

	public static function GetTypesHTML($IBLOCK_ID, $name, $value = null)
	{
		$res = '<select name="'.htmlspecialcharsbx($name).'" style="width: 200px;">';

		$res .= '<option value="">'.GetMessage('SP_IGNORE').'</option>';

		$arTypes = self::GetTypes($IBLOCK_ID);
		foreach ($arTypes as $val => $title)
			$res .= '<option value="'.htmlspecialcharsbx($val).'"'.($value == $val ? ' selected="selected"' : '').'>'.htmlspecialcharsex($title).'</option>';

		$res .= '</select>';

		return $res;
	}

	public static function GetTypesCreateHTML($name, $value = null)
	{
		$res = '<select name="'.htmlspecialcharsbx($name).'" style="width: 200px;">';

		$res .= '<option value="">'.GetMessage('SP_LIST_FIELD_CREATE_NONE').'</option>';

		$arTypes = self::GetTypesCreate();
		foreach ($arTypes as $val => $title)
			$res .= '<option value="'.htmlspecialcharsbx($val).'"'.($value == $val ? ' selected="selected"' : '').'>'.htmlspecialcharsex($title).'</option>';

		$res .= '</select>';

		return $res;
	}

	public static function RequestItems($ID, $arService = array())
	{
		if (!is_array($arService) || !$arService['IBLOCK_ID'])
		{
			$dbRes = self::GetByID($ID, true);
			$arService = $dbRes->Fetch();
		}

		if (is_array($arService))
		{
			if (!self::CheckService($arService, 'GetListItemChangesSinceToken'))
				return false;

			$arParams = array(
				'TOKEN' => $arService['SYNC_LAST_TOKEN'],
				'NUM_ROWS' => isset($arService['SYNC_NUM_ROWS']) ? $arService['SYNC_NUM_ROWS'] : BX_INTRANET_SP_NUM_ROWS_AUTO,
				'FIELDS' => array()
			);


			if ($arService['SYNC_PAGING'])
				$arParams['PAGING'] = $arService['SYNC_PAGING'];

			if (is_array($arService['FIELDS']))
			{
				foreach ($arService['FIELDS'] as $fld)
					$arParams['FIELDS'][] = $fld['SP_FIELD'];
			}

			//echo '<pre>'; print_r($arService); echo '</pre>';

			$handler = new $arService['HANDLER_CLASS']($arService['SP_URL']);
			return $handler->GetListItemChangesSinceToken(CIntranetUtils::makeGUID($arService['SP_LIST_ID']), $arParams);
		}
		else
		{
			return false;
		}
	}

	public static function RequestItemsNext($ID, $arAddParams = array())
	{
		global $DB;

		$dbRes = self::GetByID($ID, true);
		$arService = $dbRes->Fetch();

		if (is_array($arService))
		{
			foreach ($arAddParams as $k=>$v) $arService[$k]=$v;

			if (!self::CheckService($arService, 'GetListItemChangesSinceToken'))
			{
				return false;
			}

			if ($bFirst = (
				strlen($arService['SYNC_LAST_TOKEN']) <= 0
				||
				strlen($arService['SYNC_PAGING']) > 0
			))
			{
				$arService['SYNC_LAST_TOKEN'] = '';
			}

			$RESULT = self::RequestItems($arService['IBLOCK_ID'], $arService);

			if (!is_array($RESULT))
			{
				self::SetError($arService['IBLOCK_ID']);
				return false;
			}
			else
			{
				$RESULT['SERVICE'] = $arService;

				$arFields = array(
					'SYNC_DATE' => ConvertTimeStamp(false, 'FULL'),
					'SYNC_ERRORS' => 0,
					'SYNC_PAGING' => $RESULT['MORE_ROWS'] ? $RESULT['PAGING'] : '',
				);

				if ($RESULT['TOKEN'])
					$arFields['SYNC_LAST_TOKEN'] = $RESULT['TOKEN'];

				self::Update($ID, $arFields);

				return $RESULT;
			}
		}

		return false;
	}

	public static function Sync($arService, $row, &$arQueue)
	{
		if (self::CheckService($arService))
		{
			if (method_exists($arService['HANDLER_CLASS'], 'Sync'))
				return call_user_func_array(array($arService['HANDLER_CLASS'], 'Sync'), array($arService, $row, &$arQueue));
			else //if (CModule::IncludeModule('webservice'))
				return self::_Sync($arService, $row, $arQueue);
		}

		return false;
	}

	public static function SetError($ID)
	{
		global $DB;

		return true;

		if ($where = self::_GetWhere($ID))
			return $DB->Query('UPDATE b_intranet_sharepoint SET SYNC_DATE='.$DB->CurrentTimeFunction().', SYNC_ERRORS=SYNC_ERRORS+1 WHERE '.$where);

		return false;
	}

	public static function QueueNext($IBLOCK_ID = false, $cnt = 0)
	{
		if ($res = CIntranetSharepointQueue::Next($IBLOCK_ID, $cnt))
		{
			if (!self::CheckService($res, $res['SP_METHOD']))
				return true;

			$handler = new $res['HANDLER_CLASS']($res['SP_URL']);
			$RESULT = call_user_func_array(array($handler, $res['SP_METHOD']), array(CIntranetUtils::makeGUID($res['SP_LIST_ID']), $res['SP_METHOD_PARAMS']));

			CIntranetSharepointQueue::SetMinID($res['ID']);

			if (
				is_array($res['CALLBACK'])
				&& is_callable(array($res['CALLBACK'][0], $res['CALLBACK'][1]))
			)
			{
				$arParams = $res['CALLBACK'][2];
				$arParams[] = $RESULT;

				call_user_func_array(array($res['CALLBACK'][0], $res['CALLBACK'][1]), $arParams);
			}

			return true;
		}

		return false;
	}

	public static function ListNext($limit)
	{
		if ($ID = self::_ListNext($limit))
		{
			if ($RESULT = self::RequestItemsNext($ID))
			{
				if ($RESULT['COUNT'] > 0)
				{
					$arQueue = array();
					foreach ($RESULT['DATA'] as $arRow)
					{
						self::Sync($RESULT['SERVICE'], $arRow, $arQueue);
					}

					if (count($arQueue) > 0)
					{
						foreach ($arQueue as $item)
						{
							$item['IBLOCK_ID'] = $RESULT['SERVICE']['IBLOCK_ID'];
							CIntranetSharepointQueue::Add($item);
						}
					}

				}
			}
		}

		return $ID;
	}

	public static function UpdateNext($arCurrentRows)
	{
		if (CIntranetSharepoint::UpdateItems($arCurrentRows))
		{
			CIntranetSharepointLog::Clear(array_keys($arCurrentRows));
		}

		return true;
	}

	/* agent functions */
	private static function Log($agent, $res)
	{
		return;
		$fp = fopen($_SERVER['DOCUMENT_ROOT'].'/agents.log', 'a');
		fwrite($fp, date("c")." - ".$agent.": ".$res."\r\n");
		fclose($fp);
	}

	public static function AgentLists()
	{
		if (CBXFeatures::IsFeatureEnabled('intranet_sharepoint'))
		{
			$max_cnt = 5; $i = 0;

			while ($RESULT = self::ListNext($max_cnt))
			{
				if (++$i > $max_cnt)
					break;
			}

			self::Log("Lists", $i);

			return "CIntranetSharepoint::AgentLists();";
		}
	}

	public static function AgentQueue($IBLOCK_ID = false)
	{
		if (CBXFeatures::IsFeatureEnabled('intranet_sharepoint'))
		{
			$max_cnt = $IBLOCK_ID ? BX_INTRANET_SP_QUEUE_COUNT : BX_INTRANET_SP_QUEUE_COUNT_MANUAL;
			$i = 0;

			if (CIntranetSharepointQueue::Lock())
			{
				while ($RESULT = self::QueueNext($IBLOCK_ID, $max_cnt))
				{
					if (++$i > $max_cnt)
						break;
				}

				CIntranetSharepointQueue::Clear($IBLOCK_ID);
				CIntranetSharepointQueue::Unlock();

				self::Log("Queue", $i);
			}
			else
			{
				self::Log("Queue", "Locked!");
			}

			return "CIntranetSharepoint::AgentQueue();";
		}
	}

	public static function AgentUpdate($_IBLOCK_ID = false)
	{
		if (CBXFeatures::IsFeatureEnabled('intranet_sharepoint'))
		{
			global $DB;

			$arCurrentRows = array();
			$IBLOCK_ID = 0;
			$COUNTER = 0;

			$q = 0;

			while ($arRes = CIntranetSharepointLog::Next($_IBLOCK_ID))
			{
				if ($IBLOCK_ID > 0 && $IBLOCK_ID != $arRes['IBLOCK_ID'])
				{

					if (self::UpdateNext($arCurrentRows))
					{
						echo 1;
						if (++$COUNTER > BX_INTRANET_SP_LOG_COUNT)
							break;
					}
					echo 2;

					$arCurrentRows = array();
				}

				$arCurrentRows[$arRes['ID']] = $arRes;
				$IBLOCK_ID = $arRes['IBLOCK_ID'];
			}

			if ((count($arCurrentRows) > 0) && self::UpdateNext($arCurrentRows))
				$COUNTER++;

			self::Log("Update", $COUNTER);

			return 'CIntranetSharepoint::AgentUpdate();';
		}
	}

	public static function UpdateItems($arRows)
	{
		list(,$row) = each($arRows);

		$IBLOCK_ID = $row['IBLOCK_ID'];
		$PRIORITY = $row['PRIORITY'];

		$dbRes = CIntranetSharepoint::GetByID($IBLOCK_ID, true);
		if ($arService = $dbRes->Fetch())
		{
			if (!self::CheckService($arService, 'UpdateListItems'))
				return false;

			$arIDs = array();
			$arSelect = array('ID', 'XML_ID');
			foreach ($arRows as $row) $arIDs[] = $row['ELEMENT_ID'];
			foreach ($arService['FIELDS'] as $fld) $arSelect[] = $fld['FIELD_ID'];

			$dbRes = CIBlockElement::GetList(array(), array(
				'IBLOCK_ID' => $IBLOCK_ID,
				'ID' => $arIDs,
			), false, false, $arSelect);

			$arChanges = array();
			$arChangesIDs = array();
			while ($obItem = $dbRes->GetNextElement())
			{
				$arItem = $obItem->GetFields();
				$arProp = $obItem->GetProperties();

				$arChange = array();

				foreach ($arService['FIELDS'] as $fld)
				{
					if (substr($fld['FIELD_ID'], 0, 9) === 'PROPERTY_')
						$fld['FIELD_ID'].= '_VALUE';

					if ($val = self::_UpdateGetValueByType($arItem[$fld['FIELD_ID']], $fld['SP_FIELD_TYPE']))
					{
						$arChange[$fld['SP_FIELD']] = $val;
					}
				}

				if ($arItem['XML_ID'] && ($arItem['XML_ID'] != $arItem['ID']))
				{
					$arChangesIDs[] = $arItem['XML_ID'];

					$arChange['UniqueId'] = $arItem['XML_ID'];
					$arChange['ID'] = intval($arItem['XML_ID']);
				}

				foreach ($arProp as $prop)
				{
					if ($prop['CODE'] == 'OWSHIDDENVERSION')
					{
						$arChange['owshiddenversion'] = $prop['VALUE'];
						$arChange['Metainfo_vti_versionhistory'] = md5($prop['VALUE'].'|'.$arItem['XML_ID']).':'.$prop['VALUE'];
					}
				}

				$arItemsMap = array();
				if (!$arChange['UniqueId'])
				{
					$arChange['ReplicationID'] = CIntranetUtils::makeGUID(md5($arItem['ID']));
					$arChanges[$arChange['ReplicationID']] = $arChange;

					$arItemsMap[$arChange['ReplicationID']] = $arItem['ID'];
				}
				else
				{
					$arChanges[$arChange['ID']] = $arChange;
					$arItemsMap[$arChange['ID']] = $arItem['ID'];
				}
			}

			$handler = new $arService['HANDLER_CLASS']($arService['SP_URL']);
			$RESULT = $handler->GetByID(CIntranetUtils::makeGUID($arService['SP_LIST_ID']), $arChangesIDs);

			if (count($RESULT) > 0)
			{
				foreach ($RESULT as $sp_row)
				{
					foreach ($sp_row  as $fld=>$val)
					{
						if (!isset($arChanges[$sp_row['ID']][$fld]))
						{
							$arChanges[$sp_row['ID']][$fld] = $val;
						}
					}
				}
			}

			$arSecondQuery = array();
			$arQueue = array();

			CIntranetSharepoint::$bUpdateInProgress = true;
			if ($RESULT = $handler->UpdateListItems(CIntranetUtils::makeGUID($arService['SP_LIST_ID']), $arChanges))
			{
				foreach ($RESULT as $res)
				{
					$ID = 0;
					$arUpdateFields = array();
					$arUpdateProps = array();

					// version conflict
					if ($res['ErrorCode'] == '0x81020015')
					{
						if ($PRIORITY == 'B') // 'B'itrix - our row is preferrable
						{
							$new_version = $res['Row']['owshiddenversion'];
							$arChange = $arChanges[$res['Row']['ID']];
							$arChange['owshiddenversion'] = $new_version;
							$arSecondQuery[] = $arChange;

							$arUpdateProps['OWSHIDDENVERSION'] = $new_version;
							$ID = $arItemsMap[$res['Row']['ID']];
						}
						else // 'S'harepoint - remote row is preferrable
						{
							self::_Sync($arService, $res['Row'], $arQueue);
						}
					}

					if ($res['Row']['MetaInfo_ReplicationID'])
					{
						$arUpdateFields['XML_ID'] = $res['Row']['UniqueId'];
						$arUpdateProps['OWSHIDDENVERSION'] = $res['Row']['owshiddenversion'];

						$ID = $arItemsMap[$res['Row']['MetaInfo_ReplicationID']];
					}

					if ($ID > 0)
					{
						if (count($arUpdateFields) > 0)
						{
							$ob = new CIBlockElement();
							$ob->Update($ID, $arUpdateFields);
						}
						if (count($arUpdateProps) > 0)
						{
							CIBlockElement::SetPropertyValuesEx($ID, false, $arUpdateProps);
						}
					}
				}

				if (count($arSecondQuery))
				{
					$handler->UpdateListItems(CIntranetUtils::makeGUID($arService['SP_LIST_ID']), $arSecondQuery);
				}
			}
			CIntranetSharepoint::$bUpdateInProgress = false;

			return true;
		}

		return false;
	}

	public static function SetPropertyValue($XML_ID, $FIELD, $value)
	{
		$dbRes = CIBlockElement::GetList(array(), array('XML_ID' => $XML_ID, "CHECK_PERMISSIONS" => "N"));
		if ($arEl = $dbRes->Fetch())
		{
			if ($prop = self::__prop($FIELD))
			{
				CIBlockElement::SetPropertyValuesEx($arEl['ID'], false, array($prop => $value));
			}
			else
			{
				if (is_array($value) && count($value) == 1)
					$value = $value[0];

				$obEl = new CIBlockElement();
				$obEl->Update($arEl['ID'], array($FIELD => $value));
			}
		}

		return true;
	}

	public static function CheckAccess($IBLOCK_ID)
	{
		$result = null;

		$events = GetModuleEvents("intranet", "OnSharepointCheckAccess");
		while ($arEvent = $events->Fetch())
		{
			$res = ExecuteModuleEventEx($arEvent, array($IBLOCK_ID));

			if ($res === false)
				return false;
			elseif ($res === true)
				$result = true;
		}

		if (null === $result)
		{
			$result = (CIBlock::GetPermission($IBLOCK_ID) >= 'W');
		}

		return $result;
	}

	/* protected section */

	protected static function CheckFields($action, &$arFields)
	{
		global $APPLICATION;

		if ($action == 'UPDATE')
			unset($arFields['IBLOCK_ID']);

		$arFields['SYNC_INTERVAL'] = intval($arFields['SYNC_INTERVAL']);

		if ($action == 'ADD')
		{
			$arFields['IBLOCK_ID'] = intval($arFields['IBLOCK_ID']);

			if (!$arFields['IBLOCK_ID'])
			{
				$APPLICATION->ThrowException(GetMessage('SP_ERROR_WRONG_IBLOCK_ID'));
				return false;
			}
		}

		if ($action == 'ADD' || isset($arFields['SP_LIST_ID']))
		{
			if (!($arFields['SP_LIST_ID'] = CIntranetUtils::checkGUID($arFields['SP_LIST_ID'])))
			{
				$APPLICATION->ThrowException(GetMessage('SP_ERROR_WRONG_SP_LIST_ID'));
				return false;
			}
		}

		if ($action == 'ADD')
		{
			$r = self::GetByID($arFields['IBLOCK_ID']);
			if ($r->Fetch())
			{
				$APPLICATION->ThrowException(GetMessage('SP_ERROR_IBLOCK_EXISTS'));
				return false;
			}

			$r = self::GetByID($arFields['SP_LIST_ID']);
			if ($r->Fetch())
			{
				$APPLICATION->ThrowException(GetMessage('SP_ERROR_LIST_EXISTS'));
				return false;
			}
		}

		if ($action == 'ADD' || isset($arFields['SP_URL']))
		{
			$URL = parse_url($arFields['SP_URL']);

			if (!is_array($URL) || !isset($URL['host']))
			{
				$APPLICATION->ThrowException(GetMessage('SP_ERROR_WRONG_URL'));
				return false;
			}
		}

		// zero is a valid value
		if (isset($arFields['SYNC_PERIOD']))
			$arFields['SYNC_PERIOD'] = intval($arFields['SYNC_PERIOD']);

		if ($action == 'ADD')
		{
			if (!$arFields['HANDLER_MODULE'])
				$arFields['HANDLER_MODULE'] = 'webservice';
		}

		if (isset($arFields['HANDLER_MODULE']) && $arFields['HANDLER_CLASS'])
		{
			if (!CModule::IncludeModule($arFields['HANDLER_MODULE'])) //we should use IncludeModule instead of isModuleInstalled! so we can check class_exists().
			{
				$APPLICATION->ThrowException(GetMessage('SP_ERROR_MODULE_NOT_INSTALLED').': '.$arFields['HANDLER_MODULE']);
				return false;
			}
		}

		if ($action == 'ADD')
		{
			if (!$arFields['HANDLER_CLASS'])
				$arFields['HANDLER_CLASS'] = 'CSPListsClient';
			elseif (!class_exists($arFields['HANDLER_CLASS']))
			{
				$APPLICATION->ThrowException(GetMessage('SP_ERROR_CLASS_NOT_EXISTS').': '.$arFields['HANDLER_CLASS']);
				return false;
			}
		}

		if ($action == 'ADD' || isset($arFields['PRIORITY']))
		{
			$arFields['PRIORITY'] = $arFields['PRIORITY'] == 'S' ? 'S' : 'B';
		}

		return true;
	}

	protected static function CheckService(&$arService, $method = '')
	{
		global $APPLICATION;

		if ($arService['SYNC_ERRORS'] >= BX_INTRANET_SP_MAX_ERRORS)
		{
			$APPLICATION->ThrowException(GetMessage('SP_ERROR_MAX_ERRORS'));

			return false;
		}

		if ($arService['HANDLER_MODULE'])
		{
			if (!CModule::IncludeModule($arService['HANDLER_MODULE']))
			{
				$APPLICATION->ThrowException(GetMessage('SP_ERROR_MODULE_NOT_INSTALLED').': '.$arService['HANDLER_MODULE']);

				return false;
			}
		}

		if (!class_exists($arService['HANDLER_CLASS']))
		{
			$APPLICATION->ThrowException(GetMessage('SP_ERROR_CLASS_NOT_EXISTS').': '.$arService['HANDLER_CLASS']);
			return false;
		}

		if ($method && !method_exists($arService['HANDLER_CLASS'], $method))
		{
			$APPLICATION->ThrowException(GetMessage('SP_ERROR_METHOD_NOT_EXISTS', array('#CLASS#' => $arService['HANDLER_CLASS'], '#METHOD#' => $method)));
			return false;
		}

		if (!is_array($arService['SP_URL']))
			$URL = parse_url($arService['SP_URL']);
		else
			$URL = $arService['SP_URL'];

		if (!is_array($URL) || !isset($URL['host']))
		{
			$APPLICATION->ThrowException(GetMessage('SP_ERROR_WRONG_URL'));
			return false;
		}


		if ($arService['SP_AUTH_USER'])
		{
			$URL['user'] = $arService['SP_AUTH_USER'];
			$URL['pass'] = $arService['SP_AUTH_PASS'];
		}

		$arService['SP_URL'] = $URL;

		return true;
	}

	protected static function _GetWhere($ID)
	{
		if ($SP_LIST_ID = CIntranetUtils::checkGUID($ID))
			return 'SP_LIST_ID=\''.$SP_LIST_ID.'\'';
		else
		{
			$ID = intval($ID);
			if ($ID <= 0)
				return false;

			return 'IBLOCK_ID=\''.$ID.'\'';
		}
	}


	// TODO: think how to return "local entry is newer" fault
	protected function _Sync($arService, $row, &$arQueue)
	{
		$IBLOCK_ID = $arService['IBLOCK_ID'];

		if (!is_array($arQueue)) $arQueue = array();

		$arFields = array(
			'IBLOCK_ID' => $IBLOCK_ID,
			'XML_ID' => $row['UniqueId'],
		);

		$arProperties = array();

		foreach ($arService['FIELDS'] as $fld)
		{
			$arValue = self::_SyncGetValueByType(array(
				'FIELD' => $fld,
				'VALUE' => $row[$fld['SP_FIELD']],
				'ROW' => $row,
				'SP_LIST_ID' => $arService['SP_LIST_ID'],
			), $arQueue);

			if (null !== $arValue['VALUE'])
			{
				if ($arValue['PROPERTY'])
					$arProperties[$arValue['PROPERTY']] = $arValue['VALUE'];
				else
					$arFields[$arValue['FIELD']] = $arValue['VALUE'];
			}
		}

		$ib = new CIBlockElement();
		$dbRes = CIBlockElement::GetList(array('id' => 'asc'), array('IBLOCK_ID' => $IBLOCK_ID, 'XML_ID' => $arFields['XML_ID'], "CHECK_PERMISSIONS" => "N"), false, false, array('ID'));

		CIntranetSharepoint::$bUpdateInProgress = true;

		$bVersionConflict = false;
		if ($arRes = $dbRes->Fetch())
		{
			$bNew = false;

			if (false && $arService['PRIORITY'] == 'B')
			{
				if ($version = CIntranetSharepointLog::ItemUpdated($IBLOCK_ID, $arRes['ID']))
				{
					if ($row['owshiddenversion'] > $version) // ?????? rly?
					{
						$bVersionConflict = true; // we won't allow changes from SP until pushing our changes onto it.
						$ID = false;
					}
				}
			}

			$arProperties['OWSHIDDENVERSION'] = intval($row['owshiddenversion']);

			if (!$bVersionConflict)
			{
				$ib->Update(($ID = $arRes['ID']), $arFields);

				CIntranetSharepointLog::ItemUpdatedClear($IBLOCK_ID, $ID);
			}
		}
		else
		{
			$bNew = true;
			$ID = $ib->Add($arFields);
		}

		CIntranetSharepoint::$bUpdateInProgress = false;

		if (!$ID)
		{
			if (!$bVersionConflict)
			{
				$GLOBALS['APPLICATION']->ThrowException($ib->LAST_ERROR);
			}
		}
		else
		{
			if (count($arProperties) > 0)
				CIBlockElement::SetPropertyValuesEx($ID, $IBLOCK_ID, $arProperties, $bNew ? array('NewElement' => true) : array());
		}

		return $ID;
	}

	public static function AddToUpdateLog($arFields)
	{
		return CIntranetSharepointLog::Add($arFields);
	}

	public static function IsQueue($IBLOCK_ID = false)
	{
		return CIntranetSharepointQueue::IsQueue($IBLOCK_ID);
	}

	public static function IsLog($IBLOCK_ID = false)
	{
		return CIntranetSharepointLog::IsLog($IBLOCK_ID);
	}

	private static function __prop($fld)
	{
		return (substr($fld, 0, 9) == 'PROPERTY_') ? intval(substr($fld, 9)) : null;
	}

	protected function _UpdateGetValueByType($value, $type)
	{
		return $value;
	}

	protected function _SyncGetValueByType($FIELD, &$arQueue)
	{
		$fld = $FIELD['FIELD'];

		$bProperty = (($prop = self::__prop($fld['FIELD_ID'])) != null);

		$value = $FIELD['VALUE'];
		switch ($fld['SP_FIELD_TYPE'])
		{
			case 'DateTime':
				$ts = strtotime($value);
				if ($ts)
				{
					$value = ConvertTimeStamp($ts, 'FULL');
				}
			break;

			case 'Counter':
			case 'Integer':
				$value = intval($value);
			break;

			case 'Number':
				$value = doubleval($value);
			break;

			case 'User':
				$bParseAsUser = false;

				if ($bProperty)
				{
					$dbRes = CIBlockProperty::GetByID($prop, $IBLOCK_ID);
					if ($arRes = $dbRes->Fetch())
					{
						if ($arRes['USER_TYPE'] == 'UserID' || $arRes['USER_TYPE'] == 'employee')
							$bParseAsUser = true;
					}
				}
				elseif ($fld['FIELD_ID'] == 'MODIFIED_BY' || $fld['FIELD_ID'] == 'CREATED_BY')
				{
					$bParseAsUser = true;
				}

				//var_dump($value);

				if ($bParseAsUser)
					$value = self::_SyncGetUser($value);

			break;

			case 'Choice':
				if ($bProperty)
				{
					$dbRes = CIBlockProperty::GetByID($prop, $IBLOCK_ID);
					if ($arRes = $dbRes->Fetch())
					{
						if ($arRes['PROPERTY_TYPE'] == 'L')
						{
							$dbRes = CIBlockProperty::GetPropertyEnum($prop, array(), array('VALUE' => $value));
							if ($arRes = $dbRes->Fetch())
							{
								$value = $arRes['ID'];
							}
						}
					}
				}

			break;

			case 'Attachments':
				$value = intval($value);
				if ($value > 0)
				{
					$arQueue[] = array(
						'SP_METHOD' => 'GetAttachmentCollection',
						'SP_METHOD_PARAMS' => array(
							'SP_ID' => $FIELD['ROW']['ID'],
						),
						'CALLBACK' => array(
							'CIntranetSharepoint',
							'SetPropertyValue',
							array($FIELD['ROW']['UniqueId'], $fld['FIELD_ID'])
						)
					);
				}

				$value = null;

			break;
			case 'ContentTypeId':


			case 'Lookup':
			case 'Computed':

			case 'Text':
			default:
				$bParseAsFile = false;

				if ($bProperty)
				{
					$dbRes = CIBlockProperty::GetByID($prop, $IBLOCK_ID);
					if ($arRes = $dbRes->Fetch())
					{
						if ($arRes['PROPERTY_TYPE'] == 'F')
							$bParseAsFile = true;
					}
				}
				elseif ($fld['FIELD_ID'] == 'PREVIEW_PICTURE' || $fld['FIELD_ID'] == 'DETAIL_PICTURE')
				{
					$bParseAsFile = true;
				}

				if ($bParseAsFile)
				{
					$arQueue[] = array(
						'SP_METHOD' => 'LoadFile',
						'SP_METHOD_PARAMS' => array(
							'URL' => $value,
						),
						'CALLBACK' => array(
							'CIntranetSharepoint',
							'SetPropertyValue',
							array($FIELD['ROW']['UniqueId'], $fld['FIELD_ID'])
						)
					);

					$value = null;
				}
		}

		if ($bProperty)
			return array(
				'VALUE' => $value,
				'PROPERTY' => $prop
			);
		else
			return array(
				'VALUE' => $value,
				'FIELD' => $fld['FIELD_ID']
			);
	}

	/* checks existance of UF with sharepoint id. and creates it. */
	protected function _CheckUF()
	{
		static $RESULT = null;

		if (null === $RESULT)
		{
			$arFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('USER');
			if (array_key_exists(BX_INTRANET_SP_UF_NAME, $arFields))
			{
				$RESULT = BX_INTRANET_SP_UF_NAME;
			}
			else
			{
				$arUserField = array(
					'ENTITY_ID' => 'USER',
					'FIELD_NAME' => BX_INTRANET_SP_UF_NAME,
					'USER_TYPE_ID' => 'integer',
					'XML_ID' => '',
					'SORT' => 1000,
					'MULTIPLE' => 'N',
					'MANDATORY' => 'N',
					'SHOW_FILTER' => 'N',
					'SHOW_IN_LIST' => 'N',
					'EDIT_IN_LIST' => 'N',
					'IS_SEARCHABLE' => 'N',
				);

				$ob = new CUserTypeEntity();
				if ($ob->Add($arUserField))
				{
					$RESULT = BX_INTRANET_SP_UF_NAME;
				}
				else
				{
					$RESULT = false;
				}
			}
		}

		return $RESULT;
	}

	/* parse a user string from sp like this: '8;#Ivan Ivanov,#ivanov@expample.com' and try to find such user inside the system */
	protected function _SyncGetUser($user_str)
	{
		$USER_XML_ID = 0;
		$USER_ID = 0;

		list($USER_XML_ID, $FIELDS) = explode(';', $user_str);

		if ($USER_XML_ID > 0)
		{
			if (!($USER_ID = self::$arUsersCache[$USER_XML_ID]))
			{
				if ($uf_name =  self::_CheckUF())
				{
					$dbRes = CUser::GetList($by='ID', $order='ASC', array($uf_name => $USER_XML_ID));
					if ($arRes = $dbRes->Fetch())
					{
						$USER_ID = $arRes['ID'];
						self::$arUsersCache[$USER_XML_ID] = $USER_ID;
					}
				}
			}
		}

		if ($USER_ID <= 0)
		{
			$arUserFields = explode(',', substr($FIELDS, 1));
			$arKeywords = preg_split('/[^\w@.]+/', $arUserFields[1]);

			$arFilters = array(
				array('LOGIN' => $arUserFields[0]),
				array('EMAIL' => $arUserFields[0]),
				array('NAME' => $arUserFields[0]),
			);

			if (is_array($arKeywords) && count($arKeywords) > 0)
			{
				$v = implode('|', $arKeywords);

				if (strlen($v) > 0)
				{
					$arFilters[] = array('EMAIL' => $v);
					$arFilters[] = array('NAME' => $v);
				}
			}

			//echo '<pre>'; print_r($arFilters); echo '</pre>';

			foreach ($arFilters as $arFilter)
			{
				$dbRes = CUser::GetList($by='id', $order='asc', $arFilter);
				if ($arUser = $dbRes->Fetch())
				{
					$USER_ID = $arUser['ID'];
					break;
				}
			}

			if ($USER_ID && $USER_XML_ID)
			{
				$u = new CUser();
				$u->Update($USER_ID, array('UF_SP_ID' => $USER_XML_ID));

				self::$arUsersCache[$USER_XML_ID] = $USER_ID;
			}
		}

		return $USER_ID;
	}

	protected static function _ListNext($limit)
	{
		global $DB;

		if (null == self::$lists_queue)
		{
			self::$lists_queue = array();

			$query = CIntranetSharepoint::_ListNextQuery($limit);
			$dbRes = $DB->Query($query);
			while ($arList = $dbRes->Fetch())
			{
				array_push(self::$lists_queue, $arList['IBLOCK_ID']);
			}
		}

		return array_shift(self::$lists_queue);
	}

	protected static function _CreateProperty($arProp)
	{
		$arProperty = array(
			'IBLOCK_ID' => $arProp['IBLOCK_ID'],
			'NAME' => $arProp['NAME'],
			'ACTIVE' => 'Y',
			'CODE' => $arProp['CODE'],
			'PROPERTY_TYPE' => $arProp['TYPE'],
			'ROW_COUNT' => 1,
			'COL_COUNT' => 30,
			'LIST_TYPE' => $arProp['LIST_TYPE'] ? $arProp['LIST_TYPE'] : 'L',
			// the only sharepoint field that can contain files is Attachments. so F-fields should be multiple.
			'MULTIPLE' => $arProp['MULTIPLE'],
			'USER_TYPE' => $arProp['USER_TYPE'],
			'CHECK_PERMISSIONS' => 'N'
		);

		if (is_array($arProp['ENUM']))
		{
			$arProperty['VALUES'] = array();

			foreach ($arProp['ENUM'] as $key => $value)
			{
				$arProperty['VALUES'][] = array(
					'VALUE' => $value,
					'SORT' => 100*intval($key+1),
					'DEF' => $value == $arProp['ENUM_DEFAULT'] ? 'Y' : 'N',
				);
			}
		}

		$ibp = new CIBlockProperty();
		return $ibp->Add($arProperty);
	}

	protected static function PrepareSql(&$arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields)
	{
		global $DB;

		$strSqlSelect = "";
		$strSqlFrom = "";
		$strSqlWhere = "";
		$strSqlGroupBy = "";
		$strSqlOrderBy = "";

		$arGroupByFunct = array("COUNT", "AVG", "MIN", "MAX", "SUM");

		$arAlreadyJoined = array();

		// GROUP BY -->
		if (is_array($arGroupBy) && count($arGroupBy)>0)
		{
			$arSelectFields = $arGroupBy;
			foreach ($arGroupBy as $key => $val)
			{
				$val = strtoupper($val);
				$key = strtoupper($key);
				if (array_key_exists($val, $arFields) && !in_array($key, $arGroupByFunct))
				{
					if (strlen($strSqlGroupBy) > 0)
						$strSqlGroupBy .= ", ";
					$strSqlGroupBy .= $arFields[$val]["FIELD"];

					if (isset($arFields[$val]["FROM"])
						&& strlen($arFields[$val]["FROM"]) > 0
						&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
					{
						if (strlen($strSqlFrom) > 0)
							$strSqlFrom .= " ";
						$strSqlFrom .= $arFields[$val]["FROM"];
						$arAlreadyJoined[] = $arFields[$val]["FROM"];
					}
				}
			}
		}
		// <-- GROUP BY

		// SELECT -->
		$arFieldsKeys = array_keys($arFields);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSqlSelect = "COUNT(%%_DISTINCT_%% ".$arFields[$arFieldsKeys[0]]["FIELD"].") as CNT ";
		}
		else
		{
			if (isset($arSelectFields) && !is_array($arSelectFields) && is_string($arSelectFields) && strlen($arSelectFields)>0 && array_key_exists($arSelectFields, $arFields))
				$arSelectFields = array($arSelectFields);

			if (!isset($arSelectFields)
				|| !is_array($arSelectFields)
				|| count($arSelectFields)<=0
				|| in_array("*", $arSelectFields))
			{
				for ($i = 0; $i < count($arFieldsKeys); $i++)
				{
					if (isset($arFields[$arFieldsKeys[$i]]["WHERE_ONLY"])
						&& $arFields[$arFieldsKeys[$i]]["WHERE_ONLY"] == "Y")
					{
						continue;
					}

					if (strlen($strSqlSelect) > 0)
						$strSqlSelect .= ", ";

					if ($arFields[$arFieldsKeys[$i]]["TYPE"] == "datetime")
					{
						if ((strtoupper($DB->type)=="ORACLE" || strtoupper($DB->type)=="MSSQL") && (array_key_exists($arFieldsKeys[$i], $arOrder)))
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"].", ";

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD"], "FULL")." as ".$arFieldsKeys[$i];
					}
					elseif ($arFields[$arFieldsKeys[$i]]["TYPE"] == "date")
					{
						if ((strtoupper($DB->type)=="ORACLE" || strtoupper($DB->type)=="MSSQL") && (array_key_exists($arFieldsKeys[$i], $arOrder)))
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"].", ";

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD"], "SHORT")." as ".$arFieldsKeys[$i];
					}
					else
						$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i];

					if (isset($arFields[$arFieldsKeys[$i]]["FROM"])
						&& strlen($arFields[$arFieldsKeys[$i]]["FROM"]) > 0
						&& !in_array($arFields[$arFieldsKeys[$i]]["FROM"], $arAlreadyJoined))
					{
						if (strlen($strSqlFrom) > 0)
							$strSqlFrom .= " ";
						$strSqlFrom .= $arFields[$arFieldsKeys[$i]]["FROM"];
						$arAlreadyJoined[] = $arFields[$arFieldsKeys[$i]]["FROM"];
					}
				}
			}
			else
			{
				foreach ($arSelectFields as $key => $val)
				{
					$val = strtoupper($val);
					$key = strtoupper($key);
					if (array_key_exists($val, $arFields))
					{
						if (strlen($strSqlSelect) > 0)
							$strSqlSelect .= ", ";

						if (in_array($key, $arGroupByFunct))
						{
							$strSqlSelect .= $key."(".$arFields[$val]["FIELD"].") as ".$val;
						}
						else
						{
							if ($arFields[$val]["TYPE"] == "datetime")
							{
								if ((strtoupper($DB->type)=="ORACLE" || strtoupper($DB->type)=="MSSQL") && (array_key_exists($val, $arOrder)))
									$strSqlSelect .= $arFields[$val]["FIELD"].", ";

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "FULL")." as ".$val;
							}
							elseif ($arFields[$val]["TYPE"] == "date")
							{
								if ((strtoupper($DB->type)=="ORACLE" || strtoupper($DB->type)=="MSSQL") && (array_key_exists($val, $arOrder)))
									$strSqlSelect .= $arFields[$val]["FIELD"].", ";

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "SHORT")." as ".$val;
							}
							else
								$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val;
						}

						if (isset($arFields[$val]["FROM"])
							&& strlen($arFields[$val]["FROM"]) > 0
							&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
						{
							if (strlen($strSqlFrom) > 0)
								$strSqlFrom .= " ";
							$strSqlFrom .= $arFields[$val]["FROM"];
							$arAlreadyJoined[] = $arFields[$val]["FROM"];
						}
					}
				}
			}

			if (strlen($strSqlGroupBy) > 0)
			{
				if (strlen($strSqlSelect) > 0)
					$strSqlSelect .= ", ";
				$strSqlSelect .= "COUNT(%%_DISTINCT_%% ".$arFields[$arFieldsKeys[0]]["FIELD"].") as CNT";
			}
			else
				$strSqlSelect = "%%_DISTINCT_%% ".$strSqlSelect;
		}
		// <-- SELECT

		// WHERE -->
		$arSqlSearch = Array();

		if (!is_array($arFilter))
			$filter_keys = Array();
		else
			$filter_keys = array_keys($arFilter);

		for ($i = 0; $i < count($filter_keys); $i++)
		{
			$vals = $arFilter[$filter_keys[$i]];
			if (!is_array($vals))
				$vals = array($vals);

			$key = $filter_keys[$i];
			$key_res = CCatalog::GetFilterOperation($key);
			$key = $key_res["FIELD"];
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];
			$strOrNull = $key_res["OR_NULL"];

			if (array_key_exists($key, $arFields))
			{
				$arSqlSearch_tmp = array();

				if (count($vals) > 0)
				{
					if ($strOperation == "IN")
					{
						if (isset($arFields[$key]["WHERE"]))
						{
							$arSqlSearch_tmp1 = call_user_func_array(
									$arFields[$key]["WHERE"],
									array($vals, $key, $strOperation, $strNegative, $arFields[$key]["FIELD"], &$arFields, &$arFilter)
								);
							if ($arSqlSearch_tmp1 !== false)
								$arSqlSearch_tmp[] = $arSqlSearch_tmp1;
						}
						else
						{
							if ($arFields[$key]["TYPE"] == "int")
							{
								array_walk($vals, create_function("&\$item", "\$item=IntVal(\$item);"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (count($vals) <= 0)
									$arSqlSearch_tmp[] = "(1 = 2)";
								else
									$arSqlSearch_tmp[] = (($strNegative == "Y") ? " NOT " : "")."(".$arFields[$key]["FIELD"]." IN (".$val."))";
							}
							elseif ($arFields[$key]["TYPE"] == "double")
							{
								array_walk($vals, create_function("&\$item", "\$item=DoubleVal(\$item);"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (count($vals) <= 0)
									$arSqlSearch_tmp[] = "(1 = 2)";
								else
									$arSqlSearch_tmp[] = (($strNegative == "Y") ? " NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." (".$val."))";
							}
							elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
							{
								array_walk($vals, create_function("&\$item", "\$item=\"'\".\$GLOBALS[\"DB\"]->ForSql(\$item).\"'\";"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (count($vals) <= 0)
									$arSqlSearch_tmp[] = "(1 = 2)";
								else
									$arSqlSearch_tmp[] = (($strNegative == "Y") ? " NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." (".$val."))";
							}
							elseif ($arFields[$key]["TYPE"] == "datetime")
							{
								array_walk($vals, create_function("&\$item", "\$item=\"'\".\$GLOBALS[\"DB\"]->CharToDateFunction(\$GLOBALS[\"DB\"]->ForSql(\$item), \"FULL\").\"'\";"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (count($vals) <= 0)
									$arSqlSearch_tmp[] = "1 = 2";
								else
									$arSqlSearch_tmp[] = ($strNegative=="Y"?" NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." (".$val."))";
							}
							elseif ($arFields[$key]["TYPE"] == "date")
							{
								array_walk($vals, create_function("&\$item", "\$item=\"'\".\$GLOBALS[\"DB\"]->CharToDateFunction(\$GLOBALS[\"DB\"]->ForSql(\$item), \"SHORT\").\"'\";"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (count($vals) <= 0)
									$arSqlSearch_tmp[] = "1 = 2";
								else
									$arSqlSearch_tmp[] = ($strNegative=="Y"?" NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." (".$val."))";
							}
						}
					}
					else
					{
						for ($j = 0; $j < count($vals); $j++)
						{
							$val = $vals[$j];

							if (isset($arFields[$key]["WHERE"]))
							{
								$arSqlSearch_tmp1 = call_user_func_array(
										$arFields[$key]["WHERE"],
										array($val, $key, $strOperation, $strNegative, $arFields[$key]["FIELD"], &$arFields, &$arFilter)
									);
								if ($arSqlSearch_tmp1 !== false)
									$arSqlSearch_tmp[] = $arSqlSearch_tmp1;
							}
							else
							{
								if ($arFields[$key]["TYPE"] == "int")
								{
									if ((IntVal($val) == 0) && (strpos($strOperation, "=") !== False))
										$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND" : "OR")." ".(($strNegative == "Y") ? "NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." 0)";
									else
										$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".IntVal($val)." )";
								}
								elseif ($arFields[$key]["TYPE"] == "double")
								{
									$val = str_replace(",", ".", $val);

									if ((DoubleVal($val) == 0) && (strpos($strOperation, "=") !== False))
										$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND" : "OR")." ".(($strNegative == "Y") ? "NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." 0)";
									else
										$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".DoubleVal($val)." )";
								}
								elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
								{
									if ($strOperation == "QUERY")
									{
										$arSqlSearch_tmp[] = GetFilterQuery($arFields[$key]["FIELD"], $val, "Y");
									}
									else
									{
										if ((strlen($val) == 0) && (strpos($strOperation, "=") !== False))
											$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND NOT" : "OR")." (".$DB->Length($arFields[$key]["FIELD"])." <= 0) ".(($strNegative == "Y") ? "AND NOT" : "OR")." (".$arFields[$key]["FIELD"]." ".$strOperation." '".$DB->ForSql($val)."' )";
										else
											$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." '".$DB->ForSql($val)."' )";
									}
								}
								elseif ($arFields[$key]["TYPE"] == "datetime")
								{
									if (strlen($val) <= 0)
										$arSqlSearch_tmp[] = ($strNegative=="Y"?"NOT":"")."(".$arFields[$key]["FIELD"]." IS NULL)";
									else
										$arSqlSearch_tmp[] = ($strNegative=="Y"?" ".$arFields[$key]["FIELD"]." IS NULL OR NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "FULL").")";
								}
								elseif ($arFields[$key]["TYPE"] == "date")
								{
									if (strlen($val) <= 0)
										$arSqlSearch_tmp[] = ($strNegative=="Y"?"NOT":"")."(".$arFields[$key]["FIELD"]." IS NULL)";
									else
										$arSqlSearch_tmp[] = ($strNegative=="Y"?" ".$arFields[$key]["FIELD"]." IS NULL OR NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "SHORT").")";
								}
							}
						}
					}
				}

				if (isset($arFields[$key]["FROM"])
					&& strlen($arFields[$key]["FROM"]) > 0
					&& !in_array($arFields[$key]["FROM"], $arAlreadyJoined))
				{
					if (strlen($strSqlFrom) > 0)
						$strSqlFrom .= " ";
					$strSqlFrom .= $arFields[$key]["FROM"];
					$arAlreadyJoined[] = $arFields[$key]["FROM"];
				}

				$strSqlSearch_tmp = "";
				for ($j = 0; $j < count($arSqlSearch_tmp); $j++)
				{
					if ($j > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					$strSqlSearch_tmp .= "(".$arSqlSearch_tmp[$j].")";
				}
				if ($strOrNull == "Y")
				{
					if (strlen($strSqlSearch_tmp) > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." IS ".($strNegative=="Y" ? "NOT " : "")."NULL)";

					if ($arFields[$key]["TYPE"] == "int" || $arFields[$key]["TYPE"] == "double")
					{
						if (strlen($strSqlSearch_tmp) > 0)
							$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
						$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." ".($strNegative=="Y" ? "<>" : "=")." 0)";
					}
					elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
					{
						if (strlen($strSqlSearch_tmp) > 0)
							$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
						$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." ".($strNegative=="Y" ? "<>" : "=")." '')";
					}
				}

				if ($strSqlSearch_tmp != "")
					$arSqlSearch[] = "(".$strSqlSearch_tmp.")";
			}
		}

		for ($i = 0; $i < count($arSqlSearch); $i++)
		{
			if (strlen($strSqlWhere) > 0)
				$strSqlWhere .= " AND ";
			$strSqlWhere .= "(".$arSqlSearch[$i].")";
		}
		// <-- WHERE

		// ORDER BY -->
		$arSqlOrder = Array();
		foreach ($arOrder as $by => $order)
		{
			$by = strtoupper($by);
			$order = strtoupper($order);

			if ($order != "ASC")
				$order = "DESC";

			if (array_key_exists($by, $arFields))
			{
				$arSqlOrder[] = " ".$arFields[$by]["FIELD"]." ".$order." ";

				if (isset($arFields[$by]["FROM"])
					&& strlen($arFields[$by]["FROM"]) > 0
					&& !in_array($arFields[$by]["FROM"], $arAlreadyJoined))
				{
					if (strlen($strSqlFrom) > 0)
						$strSqlFrom .= " ";
					$strSqlFrom .= $arFields[$by]["FROM"];
					$arAlreadyJoined[] = $arFields[$by]["FROM"];
				}
			}
		}

		$strSqlOrderBy = "";
		DelDuplicateSort($arSqlOrder); for ($i = 0; $i < count($arSqlOrder); $i++)
		{
			if (strlen($strSqlOrderBy) > 0)
				$strSqlOrderBy .= ", ";
			if(strtoupper($DB->type)=="ORACLE")
			{
				if(substr($arSqlOrder[$i], -3)=="ASC")
					$strSqlOrderBy .= $arSqlOrder[$i]." NULLS FIRST";
				else
					$strSqlOrderBy .= $arSqlOrder[$i]." NULLS LAST";
			}
			else
				$strSqlOrderBy .= $arSqlOrder[$i];
		}
		// <-- ORDER BY

		return array(
			"SELECT" => $strSqlSelect,
			"FROM" => $strSqlFrom,
			"WHERE" => $strSqlWhere,
			"GROUPBY" => $strSqlGroupBy,
			"ORDERBY" => $strSqlOrderBy
		);
	}
}
?>
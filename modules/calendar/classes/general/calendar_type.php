<?
use Bitrix\Main\Loader;
use Bitrix\Calendar\Util;
use Bitrix\Main\UserTable;

class CCalendarType
{
	private static
		$Permissions = array(),
		$arOp = array(),
		$userOperationsCache = array();

	public static function GetList($params = array())
	{
		global $DB;
		$access = new CAccess();
		$access->UpdateCodes();
		$arFilter = $params['arFilter'];
		$result = false;
		$cacheId = false;
		$cachePath = '';
		$arOrder = isset($params['arOrder']) ? $params['arOrder'] : Array('XML_ID' => 'asc');
		$checkPermissions = $params['checkPermissions'] !== false;

		$bCache = CCalendar::CacheTime() > 0;

		if ($bCache)
		{
			$cache = new CPHPCache;
			$cacheId = serialize(array('type_list', $arFilter, $arOrder));
			$cachePath = CCalendar::CachePath().'type_list';

			if ($cache->InitCache(CCalendar::CacheTime(), $cacheId, $cachePath))
			{
				$res = $cache->GetVars();
				$result = $res["arResult"];
				$arTypeXmlIds = $res["arTypeXmlIds"];
			}
		}

		if (!$bCache || !isset($arTypeXmlIds))
		{
			static $arFields = array(
				"XML_ID" => Array("FIELD_NAME" => "CT.XML_ID", "FIELD_TYPE" => "string"),
				"NAME" => Array("FIELD_NAME" => "CT.NAME", "FIELD_TYPE" => "string"),
				"ACTIVE" => Array("FIELD_NAME" => "CT.ACTIVE", "FIELD_TYPE" => "string"),
				"DESCRIPTION" => Array("FIELD_NAME" => "CT.DESCRIPTION", "FIELD_TYPE" => "string"),
				"EXTERNAL_ID" => Array("FIELD_NAME" => "CT.EXTERNAL_ID", "FIELD_TYPE" => "string")
			);

			$arSqlSearch = array();
			if(is_array($arFilter))
			{
				$filter_keys = array_keys($arFilter);
				for($i=0, $l = count($filter_keys); $i<$l; $i++)
				{
					$n = mb_strtoupper($filter_keys[$i]);
					$val = $arFilter[$filter_keys[$i]];
					if(is_string($val) && $val == '')
						continue;
					if ($n == 'XML_ID')
					{
						if (is_array($val))
						{
							$strXml = "";
							foreach($val as $xmlId)
								$strXml .= ",'".$DB->ForSql($xmlId)."'";
							$arSqlSearch[] = "CT.XML_ID in (".trim($strXml, ", ").")";
						}
						else
						{
							$arSqlSearch[] = GetFilterQuery("CT.XML_ID", $val, 'N');
						}
					}
					if ($n == 'EXTERNAL_ID')
					{
						$arSqlSearch[] = GetFilterQuery("CT.EXTERNAL_ID", $val, 'N');
					}
					elseif(isset($arFields[$n]))
					{
						$arSqlSearch[] = GetFilterQuery($arFields[$n]["FIELD_NAME"], $val);
					}
				}
			}

			$strOrderBy = '';
			foreach($arOrder as $by=>$order)
				if(isset($arFields[mb_strtoupper($by)]))
					$strOrderBy .= $arFields[mb_strtoupper($by)]["FIELD_NAME"].' '.(mb_strtolower($order) == 'desc' ? 'desc' : 'asc').',';

			if($strOrderBy <> '')
				$strOrderBy = "ORDER BY ".rtrim($strOrderBy, ",");

			$strSqlSearch = GetFilterSqlSearch($arSqlSearch);

			$strSql = "
				SELECT
					CT.*
				FROM
					b_calendar_type CT
				WHERE
					$strSqlSearch
				$strOrderBy";

			$res = $DB->Query($strSql, false, "Function: CCalendarType::GetList<br>Line: ".__LINE__);
			$result = Array();
			$arTypeXmlIds = Array();
			while($arRes = $res->Fetch())
			{
				$result[] = $arRes;
				$arTypeXmlIds[] = $arRes['XML_ID'];
			}

			if ($bCache && isset($cache))
			{
				$cache->StartDataCache(CCalendar::CacheTime(), $cacheId, $cachePath);
				$cache->EndDataCache(array(
					"arResult" => $result,
					"arTypeXmlIds" => $arTypeXmlIds
				));
			}
		}

		if ($checkPermissions && isset($arTypeXmlIds) && count($arTypeXmlIds) > 0)
		{
			$arPerm = self::GetArrayPermissions($arTypeXmlIds);
			$res = array();
			$arAccessCodes = array();
			if (is_array($result))
			{
				foreach($result as $type)
				{
					$typeXmlId = $type['XML_ID'];
					if (self::CanDo('calendar_type_view', $typeXmlId))
					{
						$type['PERM'] = array(
							'view' => true,
							'add' => self::CanDo('calendar_type_add', $typeXmlId),
							'edit' => self::CanDo('calendar_type_edit', $typeXmlId),
							'edit_section' => self::CanDo('calendar_type_edit_section', $typeXmlId),
							'access' => self::CanDo('calendar_type_edit_access', $typeXmlId)
						);

						if (self::CanDo('calendar_type_edit_access', $typeXmlId))
						{
							$type['ACCESS'] = array();
							if (count($arPerm[$typeXmlId]) > 0)
							{
								// Add codes to get they full names for interface
								$arAccessCodes = array_merge($arAccessCodes, array_keys($arPerm[$typeXmlId]));
								$type['ACCESS'] = $arPerm[$typeXmlId];
							}
						}
						$res[] = $type;
					}
				}
			}

			CCalendar::PushAccessNames($arAccessCodes);
			$result = $res;
		}

		return $result;
	}

	public static function Edit($params)
	{
		global $DB;
		$arFields = $params['arFields'];
		$XML_ID = preg_replace("/[^a-zA-Z0-9_]/i", "", $arFields['XML_ID']);
		$arFields['XML_ID'] = $XML_ID;
		if (!isset($arFields['XML_ID']) || $XML_ID == "")
			return false;

		//return $APPLICATION->ThrowException(GetMessage("EC_ACCESS_DENIED"));

		$access = $arFields['ACCESS'];
		unset($arFields['ACCESS']);

		if (count($arFields) > 1) // We have not only XML_ID
		{
			if ($params['NEW']) // Add
			{
				$strSql = "SELECT * FROM b_calendar_type WHERE XML_ID='".$DB->ForSql($XML_ID)."'";
				$res = $DB->Query($strSql, false, __LINE__);
				if (!($arRes = $res->Fetch()))
					$DB->Add("b_calendar_type", $arFields, array('DESCRIPTION'));
				else
					false;
			}
			else // Update
			{
				unset($arFields['XML_ID']);
				if (count($arFields) > 0)
				{
					$strUpdate = $DB->PrepareUpdate("b_calendar_type", $arFields);
					$strSql =
						"UPDATE b_calendar_type SET ".
						$strUpdate.
						" WHERE XML_ID='".$DB->ForSql($XML_ID)."'";
					$DB->QueryBind($strSql, array('DESCRIPTION' => $arFields['DESCRIPTION']));
				}
			}
		}

		//SaveAccess
		if (self::CanDo('calendar_type_edit_access', $XML_ID) && is_array($access))
		{
			self::SavePermissions($XML_ID, $access);
		}

		CCalendar::ClearCache('type_list');
		return $XML_ID;
	}

	public static function Delete($XML_ID)
	{
		global $DB;
		// Del types
		$DB->Query("DELETE FROM b_calendar_type WHERE XML_ID='".$DB->ForSql($XML_ID)."'", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		// Del access for types
		$DB->Query("DELETE FROM b_calendar_access WHERE SECT_ID='".$DB->ForSql($XML_ID)."'", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		// Del sections
		$DB->Query("DELETE FROM b_calendar_section WHERE CAL_TYPE='".$DB->ForSql($XML_ID)."'", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		// Del events
		$DB->Query("DELETE FROM b_calendar_event WHERE CAL_TYPE='".$DB->ForSql($XML_ID)."'", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		CCalendar::ClearCache(array('type_list', 'section_list', 'event_list'));
		return true;
	}

	public static function SavePermissions($type, $taskPerm)
	{
		global $DB;
		$DB->Query("DELETE FROM b_calendar_access WHERE SECT_ID='".$DB->ForSql($type)."'", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		if (is_array($taskPerm))
		{
			foreach ($taskPerm as $accessCode => $taskId)
			{
				if (preg_match('/^SG/', $accessCode))
				{
					$accessCode = self::prepareGroupCode($accessCode);
				}
				
				$insert = $DB->PrepareInsert(
					"b_calendar_access",
					[
						"ACCESS_CODE" => $accessCode,
						"TASK_ID" => (int)$taskId,
						"SECT_ID" => $type
					]
				);
				
				$strSql = "INSERT INTO b_calendar_access(" . $insert[0] . ") VALUES(" . $insert[1] . ")";
				$DB->Query($strSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);
			}
		}
	}
	
	private static function prepareGroupCode($code)
	{
		$parsedCode = explode('_', $code);
		
		if (count($parsedCode) === 1)
		{
			$code .= '_K';
		}
		
		return $code;
	}
	
	
	public static function GetArrayPermissions($arTypes = array())
	{
		global $DB;
		$s = "'0'";
		foreach($arTypes as $xmlid)
			$s .= ",'".$DB->ForSql($xmlid)."'";

		$strSql = 'SELECT *
			FROM b_calendar_access CAP
			WHERE CAP.SECT_ID in ('.$s.')';
		$res = $DB->Query($strSql , false, "File: ".__FILE__."<br>Line: ".__LINE__);

		while($arRes = $res->Fetch())
		{
			$xmlId = $arRes['SECT_ID'];
			if (!is_array(self::$Permissions[$xmlId]))
				self::$Permissions[$xmlId] = array();
			self::$Permissions[$xmlId][$arRes['ACCESS_CODE']] = $arRes['TASK_ID'];
		}
		foreach($arTypes as $xmlid)
			if (!isset(self::$Permissions[$xmlid]))
				self::$Permissions[$xmlid] = array();

		return self::$Permissions;
	}

	public static function CanDo($operation, $xmlId = 0, $userId = false)
	{
		global $USER;
		if ((!$USER || !is_object($USER)) || $USER->CanDoOperation('edit_php'))
		{
			return true;
		}

		if (!is_numeric($userId))
		{
			$userId = CCalendar::GetCurUserId();
		}

		if (
			CCalendar::IsBitrix24()
			&& Loader::includeModule('bitrix24')
			&& \CBitrix24::isPortalAdmin($userId)
		)
		{
			return true;
		}

		if (($xmlId === 'group' || $xmlId === 'user' || CCalendar::IsBitrix24())
			&& CCalendar::IsSocNet()
			&& CCalendar::IsSocnetAdmin()
		)
		{
			return true;
		}

		return in_array($operation, self::GetOperations($xmlId, $userId));
	}

	public static function GetOperations($xmlId, $userId = false)
	{
		global $USER;
		if ($userId === false)
		{
			$userId = CCalendar::GetCurUserId();
		}

		$opCacheKey = $xmlId.'_'.$userId;

		if (is_array(self::$userOperationsCache[$opCacheKey]))
		{
			$result = self::$userOperationsCache[$opCacheKey];
		}
		else
		{
			$arCodes = [];
			if ($userId)
			{
				$arCodes = Util::getUserAccessCodes($userId);
			}

			if(!in_array('G2', $arCodes))
			{
				$arCodes[] = 'G2';
			}

			if($userId && !in_array('AU', $arCodes) && $USER && (int)$USER->GetId() == $userId)
			{
				$arCodes[] = 'AU';
			}

			if($userId && !in_array('UA', $arCodes) && $USER && (int)$USER->GetId() == $userId)
			{
				$arCodes[] = 'UA';
			}

			$key = $xmlId.'|'.implode(',', $arCodes);
			if(!is_array(self::$arOp[$key]))
			{
				if(!isset(self::$Permissions[$xmlId]))
					self::GetArrayPermissions(array($xmlId));
				$perms = self::$Permissions[$xmlId];

				self::$arOp[$key] = array();
				if(is_array($perms))
				{
					foreach($perms as $code => $taskId)
					{
						if(in_array($code, $arCodes))
						{
							self::$arOp[$key] = array_merge(self::$arOp[$key], CTask::GetOperations($taskId, true));
						}
					}
				}
			}
			$result = self::$userOperationsCache[$opCacheKey] = self::$arOp[$key];
		}

		return $result;
	}
}
?>

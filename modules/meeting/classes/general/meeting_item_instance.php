<?
abstract class CAllMeetingInstance
{
	const TYPE_AGENDA = 'A';
	const TYPE_TASK = 'T';

	abstract public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array());

	public static function Add($arFields)
	{
		global $DB;

		$e = GetModuleEvents('meeting', 'OnBeforeMeetingInstanceAdd');
		while ($a = $e->Fetch())
		{
			if (false === ExecuteModuleEventEx($a, array(&$arFields)))
			{
				return false;
			}
		}

		if (!self::CheckFields('ADD', $arFields))
		{
			return false;
		}

		$ID = $DB->Add('b_meeting_instance', $arFields);
		if ($ID > 0)
		{
			$arFields['ID'] = $ID;
			$arFields['INSTANCE_ID'] = $ID;

			if (isset($arFields['RESPONSIBLE']))
			{
				self::SetResponsible($arFields, $arFields['RESPONSIBLE'], false);
			}

			if (isset($arFields['REPORTS']) && is_array($arFields['REPORTS']))
			{
				foreach ($arFields['REPORTS'] as $arReport)
				{
					$arReport['MEETING_ID'] = $arFields['MEETING_ID'];
					$arReport['ITEM_ID'] = $arFields['ITEM_ID'];
					$arReport['INSTANCE_ID'] = $arFields['ID'];

					CMeetingReports::Add($arReport);
				}
			}

			$e = GetModuleEvents('meeting', 'OnAfterMeetingInstanceAdd');
			while ($a = $e->Fetch())
			{
				ExecuteModuleEventEx($a, array($arFields));
			}
		}

		return $ID;
	}

	public static function Update($ID, $arFields)
	{
		global $DB;

		if ($ID <= 0)
			return false;

		$arFields['ID'] = $ID;

		$e = GetModuleEvents('meeting', 'OnBeforeMeetingInstanceUpdate');
		while ($a = $e->Fetch())
		{
			if (false === ExecuteModuleEventEx($a, array(&$arFields)))
			{
				return false;
			}
		}

		if (!self::CheckFields('UPDATE', $arFields))
			return false;

		$strUpdate = $DB->PrepareUpdate('b_meeting_instance', $arFields);
		$bSkipUpdate = $strUpdate == '';

		if (!$bSkipUpdate)
		{
			$query = 'UPDATE b_meeting_instance SET '.$strUpdate.' WHERE ID=\''.intval($ID).'\'';

			$dbRes = $DB->Query($query);
		}

		if ($bSkipUpdate || $dbRes)
		{
			if (isset($arFields['RESPONSIBLE']))
			{
				$arFields['INSTANCE_ID'] = $ID;
				self::SetResponsible($arFields, $arFields['RESPONSIBLE']);
			}

			$e = GetModuleEvents('meeting', 'OnAfterMeetingInstanceUpdate');
			while ($a = $e->Fetch())
				ExecuteModuleEventEx($a, array($ID, $arFields));

			return $ID;
		}

		return false;
	}

	public static function SetResponsible($arInstance, $arUsers = null, $bClear = true)
	{
		global $DB;

		if ($bClear)
		{
			$DB->Query("DELETE FROM b_meeting_instance_users WHERE INSTANCE_ID='".(int)$arInstance['INSTANCE_ID']."'");
		}

		$cnt = 0;
		if (is_array($arUsers))
		{
			foreach ($arUsers as $USER_ID)
			{
				$USER_ID = (int)$USER_ID;
				if ($USER_ID <= 0)
				{
					continue;
				}

				if ($DB->Query("INSERT INTO b_meeting_instance_users (USER_ID, INSTANCE_ID, ITEM_ID, MEETING_ID) VALUES ('".$USER_ID."', '". (int)$arInstance['INSTANCE_ID'] . "', '". (int)$arInstance['ITEM_ID'] . "', '" . (int)$arInstance['MEETING_ID'] . "')", true))
				{
					$cnt++;
				}
			}
		}

		return $cnt;
	}

	public static function GetResponsible($ID)
	{
		global $DB;

		$arUsers = array();

		$ID = intval($ID);
		if ($ID > 0)
		{
			$dbRes = $DB->Query("SELECT USER_ID FROM b_meeting_instance_users WHERE INSTANCE_ID='".$ID."'");
			while ($arRes = $dbRes->Fetch())
			{
				$arUsers[] = $arRes['USER_ID'];
			}
		}

		return $arUsers;
	}

	public static function GetReports($ID)
	{
		return CMeetingReports::GetList(array(), array('INSTANCE_ID' => $ID));
	}

	public static function Delete($ID, $bSkipCheckItems = false)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID < 1)
			return false;

		$rsEvents = GetModuleEvents("meeting", "OnBeforeMeetingInstanceDelete");
		while ($arEvent = $rsEvents->Fetch())
		{
			if (false === ExecuteModuleEventEx($arEvent, array($ID)))
			{
				return false;
			}
		}

		CMeetingReports::DeleteByInstanceID($ID);
		$DB->Query("DELETE FROM b_meeting_instance_users WHERE INSTANCE_ID='".$ID."'");

		if ($DB->Query("DELETE FROM b_meeting_instance WHERE ID='".$ID."'"))
		{
			if (!$bSkipCheckItems)
				CMeetingItem::DeleteAbandoned();

			$rsEvents = GetModuleEvents("meeting", "OnAfterMeetingInstanceDelete");
			while ($arEvent = $rsEvents->Fetch())
				ExecuteModuleEventEx($arEvent, array($ID));

			return true;
		}

		return false;
	}

	public static function DeleteByItemID($ITEM_ID)
	{
		global $DB;

		$ITEM_ID = intval($ITEM_ID);
		if ($ITEM_ID < 1)
			return false;

		CMeetingReports::DeleteByItemID($ITEM_ID);
		$DB->Query("DELETE FROM b_meeting_instance_users WHERE ITEM_ID='".$ITEM_ID."'");

		return $DB->Query("DELETE FROM b_meeting_instance WHERE ITEM_ID='".$ITEM_ID."'");
	}

	public static function DeleteByMeetingID($MEETING_ID)
	{
		global $DB;

		$MEETING_ID = intval($MEETING_ID);
		if ($MEETING_ID < 1)
			return false;

		CMeetingReports::DeleteByMeetingID($MEETING_ID);
		$DB->Query("DELETE FROM b_meeting_instance_users WHERE MEETING_ID='".$MEETING_ID."'");

		if ($DB->Query("DELETE FROM b_meeting_instance WHERE MEETING_ID='".$MEETING_ID."'"))
		{
			CMeetingItem::DeleteAbandoned();
			return true;
		}

		return false;
	}

	protected static function CheckFields($action, &$arFields)
	{
		unset($arFields['ID']);

		if ($action == 'ADD')
		{
			if (!$arFields['ITEM_ID'] || !$arFields['MEETING_ID'])
				return false;
		}

		if (isset($arFields['INSTANCE_TYPE']) && !in_array($arFields['INSTANCE_TYPE'], array(
			self::TYPE_AGENDA,
			self::TYPE_TASK,
		)))
			unset($arFields['INSTANCE_TYPE']);

		return true;
	}

	protected static function GetFilterOperation($key)
	{
		$strNegative = "N";
		if (mb_substr($key, 0, 1) == "!")
		{
			$key = mb_substr($key, 1);
			$strNegative = "Y";
		}

		$strOrNull = "N";
		if (mb_substr($key, 0, 1) == "+")
		{
			$key = mb_substr($key, 1);
			$strOrNull = "Y";
		}

		if (mb_substr($key, 0, 2) == ">=")
		{
			$key = mb_substr($key, 2);
			$strOperation = ">=";
		}
		elseif (mb_substr($key, 0, 1) == ">")
		{
			$key = mb_substr($key, 1);
			$strOperation = ">";
		}
		elseif (mb_substr($key, 0, 2) == "<=")
		{
			$key = mb_substr($key, 2);
			$strOperation = "<=";
		}
		elseif (mb_substr($key, 0, 1) == "<")
		{
			$key = mb_substr($key, 1);
			$strOperation = "<";
		}
		elseif (mb_substr($key, 0, 1) == "@")
		{
			$key = mb_substr($key, 1);
			$strOperation = "IN";
		}
		elseif (mb_substr($key, 0, 1) == "~")
		{
			$key = mb_substr($key, 1);
			$strOperation = "LIKE";
		}
		elseif (mb_substr($key, 0, 1) == "%")
		{
			$key = mb_substr($key, 1);
			$strOperation = "QUERY";
		}
		else
		{
			$strOperation = "=";
		}

		return array("FIELD" => $key, "NEGATIVE" => $strNegative, "OPERATION" => $strOperation, "OR_NULL" => $strOrNull);
	}

	protected static function PrepareSql(&$arFields, $arOrder, &$arFilter, $arGroupBy, $arSelectFields)
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
				$val = mb_strtoupper($val);
				$key = mb_strtoupper($key);
				if (array_key_exists($val, $arFields) && !in_array($key, $arGroupByFunct))
				{
					if ($strSqlGroupBy <> '')
						$strSqlGroupBy .= ", ";
					$strSqlGroupBy .= $arFields[$val]["FIELD"];

					if (isset($arFields[$val]["FROM"])
						&& $arFields[$val]["FROM"] <> ''
						&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
					{
						if ($strSqlFrom <> '')
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
			if (isset($arSelectFields) && !is_array($arSelectFields) && is_string($arSelectFields) && $arSelectFields <> '' && array_key_exists($arSelectFields, $arFields))
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

					if ($strSqlSelect <> '')
						$strSqlSelect .= ", ";

					if ($arFields[$arFieldsKeys[$i]]["TYPE"] == "datetime")
					{
						if (($DB->type == "ORACLE" || $DB->type == "MSSQL") && (array_key_exists($arFieldsKeys[$i], $arOrder)))
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i]."_X1, ";

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD"], "FULL")." as ".$arFieldsKeys[$i];
					}
					elseif ($arFields[$arFieldsKeys[$i]]["TYPE"] == "date")
					{
						if (($DB->type == "ORACLE" || $DB->type == "MSSQL") && (array_key_exists($arFieldsKeys[$i], $arOrder)))
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i]."_X1, ";

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD"], "SHORT")." as ".$arFieldsKeys[$i];
					}
					else
						$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i];

					if (isset($arFields[$arFieldsKeys[$i]]["FROM"])
						&& $arFields[$arFieldsKeys[$i]]["FROM"] <> ''
						&& !in_array($arFields[$arFieldsKeys[$i]]["FROM"], $arAlreadyJoined))
					{
						if ($strSqlFrom <> '')
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
					$val = mb_strtoupper($val);
					$key = mb_strtoupper($key);
					if (array_key_exists($val, $arFields))
					{
						if ($strSqlSelect <> '')
							$strSqlSelect .= ", ";

						if (in_array($key, $arGroupByFunct))
						{
							$strSqlSelect .= $key."(".$arFields[$val]["FIELD"].") as ".$val;
						}
						else
						{
							if ($arFields[$val]["TYPE"] == "datetime")
							{
								if (($DB->type == "ORACLE" || $DB->type == "MSSQL") && (array_key_exists($val, $arOrder)))
									$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val."_X1, ";

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "FULL")." as ".$val;
							}
							elseif ($arFields[$val]["TYPE"] == "date")
							{
								if (($DB->type == "ORACLE" || $DB->type == "MSSQL") && (array_key_exists($val, $arOrder)))
									$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val."_X1, ";

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "SHORT")." as ".$val;
							}
							else
								$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val;
						}

						if (isset($arFields[$val]["FROM"])
							&& $arFields[$val]["FROM"] <> ''
							&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
						{
							if ($strSqlFrom <> '')
								$strSqlFrom .= " ";
							$strSqlFrom .= $arFields[$val]["FROM"];
							$arAlreadyJoined[] = $arFields[$val]["FROM"];
						}
					}
				}
			}

			if ($strSqlGroupBy <> '')
			{
				if ($strSqlSelect <> '')
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
			else
				$vals = array_values($vals);

			$key = $filter_keys[$i];
			$key_res = self::GetFilterOperation($key);
			$key = $key_res["FIELD"];
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];
			$strOrNull = $key_res["OR_NULL"];

			if (array_key_exists($key, $arFields))
			{
				$arSqlSearch_tmp = array();
				for ($j = 0; $j < count($vals); $j++)
				{
					$val = $vals[$j];
					if (isset($arFields[$key]["WHERE"]))
					{
						$arSqlSearch_tmp1 = call_user_func_array(
								$arFields[$key]["WHERE"],
								array($val, $key, $strOperation, $strNegative, $arFields[$key]["FIELD"], $arFields, $arFilter)
							);
						if ($arSqlSearch_tmp1 !== false)
							$arSqlSearch_tmp[] = $arSqlSearch_tmp1;
					}
					else
					{
						if ($arFields[$key]["TYPE"] == "int")
						{
							if ((intval($val) == 0) && (mb_strpos($strOperation, "=") !== False))
								$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND" : "OR")." ".(($strNegative == "Y") ? "NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." 0)";
							else
								$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".intval($val)." )";
						}
						elseif ($arFields[$key]["TYPE"] == "double")
						{
							$val = str_replace(",", ".", $val);

							if ((DoubleVal($val) == 0) && (mb_strpos($strOperation, "=") !== False))
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
								if (($val == '') && (mb_strpos($strOperation, "=") !== False))
									$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND NOT" : "OR")." (".$DB->Length($arFields[$key]["FIELD"])." <= 0) ".(($strNegative == "Y") ? "AND NOT" : "OR")." (".$arFields[$key]["FIELD"]." ".$strOperation." '".$DB->ForSql($val)."' )";
								else
									$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." '".$DB->ForSql($val)."' )";
							}
						}
						elseif ($arFields[$key]["TYPE"] == "datetime")
						{
							if ($val == '')
								$arSqlSearch_tmp[] = ($strNegative=="Y"?"NOT":"")."(".$arFields[$key]["FIELD"]." IS NULL)";
							else
								$arSqlSearch_tmp[] = ($strNegative=="Y"?" ".$arFields[$key]["FIELD"]." IS NULL OR NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "FULL").")";
						}
						elseif ($arFields[$key]["TYPE"] == "date")
						{
							if ($val == '')
								$arSqlSearch_tmp[] = ($strNegative=="Y"?"NOT":"")."(".$arFields[$key]["FIELD"]." IS NULL)";
							else
								$arSqlSearch_tmp[] = ($strNegative=="Y"?" ".$arFields[$key]["FIELD"]." IS NULL OR NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "SHORT").")";
						}
					}
				}

				if (isset($arFields[$key]["FROM"])
					&& $arFields[$key]["FROM"] <> ''
					&& !in_array($arFields[$key]["FROM"], $arAlreadyJoined))
				{
					if ($strSqlFrom <> '')
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
					if ($strSqlSearch_tmp <> '')
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." IS ".($strNegative=="Y" ? "NOT " : "")."NULL)";

					if ($strSqlSearch_tmp <> '')
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					if ($arFields[$key]["TYPE"] == "int" || $arFields[$key]["TYPE"] == "double")
						$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." ".($strNegative=="Y" ? "<>" : "=")." 0)";
					elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
						$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." ".($strNegative=="Y" ? "<>" : "=")." '')";
					else
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " (1=1) " : " (1=0) ");
				}

				if ($strSqlSearch_tmp != "")
					$arSqlSearch[] = "(".$strSqlSearch_tmp.")";
			}
		}

		for ($i = 0; $i < count($arSqlSearch); $i++)
		{
			if ($strSqlWhere <> '')
				$strSqlWhere .= " AND ";
			$strSqlWhere .= "(".$arSqlSearch[$i].")";
		}
		// <-- WHERE

		// ORDER BY -->
		$arSqlOrder = Array();
		foreach ($arOrder as $by => $order)
		{
			$by = mb_strtoupper($by);
			$order = mb_strtoupper($order);

			if ($order != "ASC")
				$order = "DESC";
			else
				$order = "ASC";

			if (array_key_exists($by, $arFields))
			{
				$arSqlOrder[] = " ".$arFields[$by]["FIELD"]." ".$order." ";

				if (isset($arFields[$by]["FROM"])
					&& $arFields[$by]["FROM"] <> ''
					&& !in_array($arFields[$by]["FROM"], $arAlreadyJoined))
				{
					if ($strSqlFrom <> '')
						$strSqlFrom .= " ";
					$strSqlFrom .= $arFields[$by]["FROM"];
					$arAlreadyJoined[] = $arFields[$by]["FROM"];
				}
			}
		}

		$strSqlOrderBy = "";
		DelDuplicateSort($arSqlOrder);
		for ($i=0; $i<count($arSqlOrder); $i++)
		{
			if ($strSqlOrderBy <> '')
				$strSqlOrderBy .= ", ";

			if($DB->type == "ORACLE")
			{
				if(mb_substr($arSqlOrder[$i], -3) == "ASC")
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
<?

use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Service\DependencyManager;

class CAllTimeManEntry
{
	public static function GetList($arOrder = [], $arFilter = [], $arGroupBy = false, $arNavStartParams = false, $arSelectFields = [])
	{

	}

	protected static function _GetLastQuery($USER_ID)
	{

	}

	public static function GetByID($ID)
	{
		return CTimeManEntry::GetList([], ['ID' => $ID]);
	}

	public static function GetLast($USER_ID = false)
	{
		global $DB, $USER;

		if (!$USER_ID)
		{
			$USER_ID = $USER->GetID();
		}

		$query = CTimeManEntry::_GetLastQuery($USER_ID);
		if ($query === null)
		{
			return false;
		}

		$dbRes = $DB->Query($query);
		$arRes = $dbRes->Fetch();

		if ($arRes && $arRes['TASKS'] <> '')
		{
			$arRes['TASKS'] = unserialize($arRes['TASKS'], ['allowed_classes' => false]);
		}

		return $arRes;
	}

	public static function CheckFields($action, &$arFields)
	{
		global $DB, $USER;

		$tz_diff = 0;
		if ($action == 'UPDATE')
		{
			$dbRes = CTimeManEntry::GetList([], ['ID' => $arFields['ID']]);
			if (!($arEntry = $dbRes->Fetch()))
			{
				return false;
			}

			$tz_diff = (MakeTimeStamp($arEntry['DATE_START']) % 86400) - $arEntry['TIME_START'];

			if (
				isset($arFields["DATE_START"])
				|| isset($arFields["DATE_FINISH"])
				|| isset($arFields["TIME_START"])
				|| isset($arFields["TIME_FINISH"])
			)
			{
				if (!isset($arFields['DATE_START']))
				{
					if (!isset($arFields['TIME_START']))
					{
						$arFields['DATE_START'] = $arEntry['DATE_START'];
					}
					else
					{
						$arFields['DATE_START'] = ConvertTimeStamp(
							MakeTimeStamp($arEntry['DATE_START']) + $arFields['TIME_START'] - $arEntry['TIME_START'] - $tz_diff,
							'FULL'
						);
					}
				}

				if (!isset($arFields['DATE_FINISH']))
				{
					if (!isset($arFields['TIME_FINISH']))
					{
						$arFields['DATE_FINISH'] = $arEntry['DATE_FINISH'];
					}
					else
					{
						if ($arEntry['DATE_FINISH'])
						{
							$arFields['DATE_FINISH'] = ConvertTimeStamp(
								MakeTimeStamp($arEntry['DATE_FINISH']) + $arFields['TIME_FINISH'] - $arEntry['TIME_FINISH'] - $tz_diff,
								'FULL'
							);
						}
						else
						{
							$arFields['DATE_FINISH'] = ConvertTimeStamp(
								MakeTimeStamp($arFields['DATE_START']) - $arFields['TIME_START'] + $arFields['TIME_FINISH'],
								'FULL'
							);
						}
					}
				}
			}
		}

		if ($action == 'ADD' && (!$arFields['USER_ID'] || !$arFields['DATE_START']))
		{
			return false;
		}

		$ts_start = MakeTimeStamp($arFields['DATE_START'] ?? '');
		$ts_finish = MakeTimeStamp($arFields['DATE_FINISH'] ?? '');

		if ($ts_start > 0 && $ts_finish > 0)
		{
			if ($ts_finish < $ts_start)
			{
				$ts_finish += $ts_start;
				$ts_start = $ts_finish - $ts_start;
				$ts_finish -= $ts_start;

				$arFields['DATE_START'] = ConvertTimeStamp($ts_start, 'FULL');
				$arFields['DATE_FINISH'] = ConvertTimeStamp($ts_finish, 'FULL');
			}
		}

		//ts_start and ts_finish are with correct time but for server timezone offset
		if ($ts_start > 0 && !isset($arFields['TIME_START']))
		{
			$arFields['TIME_START'] = (($ts_start + date('Z')) % 86400);
		}
		if ($ts_finish > 0 && !isset($arFields['TIME_FINISH']))
		{
			$arFields['TIME_FINISH'] = (($ts_finish + date('Z')) % 86400);
		}

		if ($action == 'ADD' || isset($arFields['ACTIVE']))
		{
			$arFields['ACTIVE'] = $arFields['ACTIVE'] == 'N' ? 'N' : 'Y';
		}
		if ($action == 'ADD' || isset($arFields['PAUSED']))
		{
			$arFields['PAUSED'] = $arFields['PAUSED'] == 'Y' ? 'Y' : 'N';
		}

		if (isset($arFields['TASKS']) && is_array($arFields['TASKS']))
		{
			$arFields['TASKS'] = serialize($arFields['TASKS']);
		}

		if (isset($arFields['TIME_LEAKS']))
		{
			$arFields['TIME_LEAKS'] = intval($arFields['TIME_LEAKS']);
		}
		elseif ($action == 'UPDATE')
		{
			$arFields['TIME_LEAKS'] = intval($arEntry['TIME_LEAKS']);
		}

		if ($ts_start > 0 && $ts_finish > 0 && $arFields['PAUSED'] != 'Y' && !isset($arFields['DURATION']))
		{
			$arFields['DURATION'] = $arFields['TIME_FINISH'] - $arFields['TIME_START'] - $arFields['TIME_LEAKS'];
		}

		if (isset($arFields['DURATION']))
		{
			$arFields['DURATION'] = intval($arFields['DURATION']);
		}
		elseif (
			$arFields['DATE_FINISH'] ?? null
			&& $arFields['PAUSED'] != 'Y'
		)
		{
			$arFields['DURATION'] = $arFields['TIME_FINISH'] - $arFields['TIME_START'] - $arFields['TIME_LEAKS'];
		}

		if (isset($arFields['TIME_LEAKS_ADD']))
		{
			$arFields['TIME_LEAKS'] += intval($arFields['TIME_LEAKS_ADD']);

			if ($arFields['DATE_FINISH'])
			{
				$arFields['DURATION'] -= intval($arFields['TIME_LEAKS_ADD']);
			}

			unset($arFields['TIME_LEAKS_ADD']);
		}

		if (isset($arFields['DURATION']))
		{
			$arFields['DURATION'] = self::correctDuration($arFields['DURATION']);
		}

		unset($arFields['ID']);
		unset($arFields['TIMESTAMP_X']);

		$arFields['MODIFIED_BY'] = $USER->GetID();

		if (isset($arFields['LAT_OPEN']))
		{
			$arFields['LAT_OPEN'] = doubleval($arFields['LAT_OPEN']);
		}

		if (isset($arFields['LON_OPEN']))
		{
			$arFields['LON_OPEN'] = doubleval($arFields['LON_OPEN']);
		}

		if (isset($arFields['LAT_CLOSE']))
		{
			$arFields['LAT_CLOSE'] = doubleval($arFields['LAT_CLOSE']);
		}

		if (isset($arFields['LON_CLOSE']))
		{
			$arFields['LON_CLOSE'] = doubleval($arFields['LON_CLOSE']);
		}

		$arFields['~TIMESTAMP_X'] = $DB->GetNowFunction();

		return true;
	}

	/**
	 * @param $arFields
	 * @deprecated Use \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordTable
	 */
	public static function Add($arFields)
	{
		global $DB;

		$e = GetModuleEvents('timeman', 'OnBeforeTMEntryAdd');
		while ($a = $e->Fetch())
		{
			if (false === ExecuteModuleEventEx($a, [$arFields]))
			{
				return false;
			}
		}

		if (!self::CheckFields('ADD', $arFields))
		{
			return false;
		}

		$id = $DB->Add('b_timeman_entries', $arFields, ['TASKS']);
		if ($id > 0)
		{
			$arFields['ID'] = $id;

			if (is_array($arFields['REPORTS']))
			{
				foreach ($arFields['REPORTS'] as $report)
				{
					$report['ENTRY_ID'] = $id;
					$report['USER_ID'] = $arFields['USER_ID'];

					CTimeManReport::Add($report);
				}
			}

			$e = GetModuleEvents('timeman', 'OnAfterTMEntryAdd');
			while ($a = $e->Fetch())
			{
				ExecuteModuleEventEx($a, [$arFields]);
			}
		}

		return $id;
	}

	/**
	 * @param $arFields
	 * @deprecated Use \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordTable
	 */
	public static function Update($id, $arFields)
	{
		global $DB, $USER;

		if ($id <= 0)
		{
			return false;
		}

		$arFields['ID'] = $id;

		$e = GetModuleEvents('timeman', 'OnBeforeTMEntryUpdate');
		while ($a = $e->Fetch())
		{
			if (false === ExecuteModuleEventEx($a, [$arFields]))
			{
				return false;
			}
		}

		if (!self::CheckFields('UPDATE', $arFields))
		{
			return false;
		}

		$strUpdate = $DB->PrepareUpdate('b_timeman_entries', $arFields);
		$query = 'UPDATE b_timeman_entries SET ' . $strUpdate . ' WHERE ID=\'' . intval($id) . '\'';

		if ($strUpdate)
		{
			$arBind = [];
			if (isset($arFields['TASKS']))
			{
				$arBind = ['TASKS' => $arFields['TASKS']];
			}
			$DB->QueryBind($query, $arBind);

			if (isset($arFields['REPORTS']) && is_array($arFields['REPORTS']))
			{
				foreach ($arFields['REPORTS'] as $report)
				{
					$report['ENTRY_ID'] = $id;
					$report['USER_ID'] = $USER->GetID(); // we need CURRENT user in this field

					CTimeManReport::Add($report);
				}
			}

			$e = GetModuleEvents('timeman', 'OnAfterTMEntryUpdate');
			while ($a = $e->Fetch())
			{
				ExecuteModuleEventEx($a, [$id, $arFields]);
			}

			return $id;
		}

		return false;
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

		return ["FIELD" => $key, "NEGATIVE" => $strNegative, "OPERATION" => $strOperation, "OR_NULL" => $strOrNull];
	}

	protected static function PrepareSql(&$arFields, $arOrder, &$arFilter, $arGroupBy, $arSelectFields, $obUserFieldsSql = false)
	{
		global $DB;

		$strSqlSelect = "";
		$strSqlFrom = "";
		$strSqlWhere = "";
		$strSqlGroupBy = "";
		$strSqlOrderBy = "";

		$arGroupByFunct = ["COUNT", "AVG", "MIN", "MAX", "SUM"];

		$arAlreadyJoined = [];

		// GROUP BY -->
		if (is_array($arGroupBy) && count($arGroupBy) > 0)
		{
			$arSelectFields = $arGroupBy;
			foreach ($arGroupBy as $key => $val)
			{
				$val = mb_strtoupper($val);
				$key = mb_strtoupper($key);
				if (array_key_exists($val, $arFields) && !in_array($key, $arGroupByFunct))
				{
					if ($strSqlGroupBy <> '')
					{
						$strSqlGroupBy .= ", ";
					}
					$strSqlGroupBy .= $arFields[$val]["FIELD"];

					if (isset($arFields[$val]["FROM"])
						&& $arFields[$val]["FROM"] <> ''
						&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
					{
						if ($strSqlFrom <> '')
						{
							$strSqlFrom .= " ";
						}
						$strSqlFrom .= $arFields[$val]["FROM"];
						$arAlreadyJoined[] = $arFields[$val]["FROM"];
					}
				}
			}
		}
		// <-- GROUP BY

		// SELECT -->
		$arFieldsKeys = array_keys($arFields);

		if (is_array($arGroupBy) && count($arGroupBy) == 0)
		{
			$strSqlSelect = "COUNT(%%_DISTINCT_%% " . $arFields[$arFieldsKeys[0]]["FIELD"] . ") as CNT ";
		}
		else
		{
			if (isset($arSelectFields) && !is_array($arSelectFields) && is_string($arSelectFields) && $arSelectFields <> '' && array_key_exists($arSelectFields, $arFields))
			{
				$arSelectFields = [$arSelectFields];
			}

			if (!isset($arSelectFields)
				|| !is_array($arSelectFields)
				|| count($arSelectFields) <= 0
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
					{
						$strSqlSelect .= ", ";
					}

					if ($arFields[$arFieldsKeys[$i]]["TYPE"] == "datetime")
					{
						if (($DB->type == "ORACLE" || $DB->type == "MSSQL") && (array_key_exists($arFieldsKeys[$i], $arOrder)))
						{
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"] . " as " . $arFieldsKeys[$i] . "_X1, ";
						}

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD"], "FULL") . " as " . $arFieldsKeys[$i];
					}
					elseif ($arFields[$arFieldsKeys[$i]]["TYPE"] == "date")
					{
						if (($DB->type == "ORACLE" || $DB->type == "MSSQL") && (array_key_exists($arFieldsKeys[$i], $arOrder)))
						{
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"] . " as " . $arFieldsKeys[$i] . "_X1, ";
						}

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD"], "SHORT") . " as " . $arFieldsKeys[$i];
					}
					else
					{
						$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"] . " as " . $arFieldsKeys[$i];
					}

					if (isset($arFields[$arFieldsKeys[$i]]["FROM"])
						&& $arFields[$arFieldsKeys[$i]]["FROM"] <> ''
						&& !in_array($arFields[$arFieldsKeys[$i]]["FROM"], $arAlreadyJoined))
					{
						if ($strSqlFrom <> '')
						{
							$strSqlFrom .= " ";
						}
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
						{
							$strSqlSelect .= ", ";
						}

						if (in_array($key, $arGroupByFunct))
						{
							$strSqlSelect .= $key . "(" . $arFields[$val]["FIELD"] . ") as " . $val;
						}
						else
						{
							if ($arFields[$val]["TYPE"] == "datetime")
							{
								if (($DB->type == "ORACLE" || $DB->type == "MSSQL") && (array_key_exists($val, $arOrder)))
								{
									$strSqlSelect .= $arFields[$val]["FIELD"] . " as " . $val . "_X1, ";
								}

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "FULL") . " as " . $val;
							}
							elseif ($arFields[$val]["TYPE"] == "date")
							{
								if (($DB->type == "ORACLE" || $DB->type == "MSSQL") && (array_key_exists($val, $arOrder)))
								{
									$strSqlSelect .= $arFields[$val]["FIELD"] . " as " . $val . "_X1, ";
								}

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "SHORT") . " as " . $val;
							}
							else
							{
								$strSqlSelect .= $arFields[$val]["FIELD"] . " as " . $val;
							}
						}

						if (isset($arFields[$val]["FROM"])
							&& $arFields[$val]["FROM"] <> ''
							&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
						{
							if ($strSqlFrom <> '')
							{
								$strSqlFrom .= " ";
							}
							$strSqlFrom .= $arFields[$val]["FROM"];
							$arAlreadyJoined[] = $arFields[$val]["FROM"];
						}
					}
				}
			}

			if ($strSqlGroupBy <> '')
			{
				if ($strSqlSelect <> '')
				{
					$strSqlSelect .= ", ";
				}
				$strSqlSelect .= "COUNT(%%_DISTINCT_%% " . $arFields[$arFieldsKeys[0]]["FIELD"] . ") as CNT";
			}
			else
			{
				$strSqlSelect = "%%_DISTINCT_%% " . $strSqlSelect;
			}
		}
		// <-- SELECT

		// WHERE -->
		$arSqlSearch = [];

		if (!is_array($arFilter))
		{
			$filter_keys = [];
		}
		else
		{
			$filter_keys = array_keys($arFilter);
		}

		for ($i = 0; $i < count($filter_keys); $i++)
		{
			$vals = $arFilter[$filter_keys[$i]];
			if (!is_array($vals))
			{
				$vals = [$vals];
			}
			else
			{
				$vals = array_values($vals);
			}

			$key = $filter_keys[$i];
			$key_res = CTimeManEntry::GetFilterOperation($key);
			$key = $key_res["FIELD"];
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];
			$strOrNull = $key_res["OR_NULL"];

			if (array_key_exists($key, $arFields))
			{
				$arSqlSearch_tmp = [];
				for ($j = 0; $j < count($vals); $j++)
				{
					$val = $vals[$j];
					if (isset($arFields[$key]["WHERE"]))
					{
						$arSqlSearch_tmp1 = call_user_func_array(
							$arFields[$key]["WHERE"],
							[$val, $key, $strOperation, $strNegative, $arFields[$key]["FIELD"], $arFields, $arFilter]
						);
						if ($arSqlSearch_tmp1 !== false)
						{
							$arSqlSearch_tmp[] = $arSqlSearch_tmp1;
						}
					}
					else
					{
						if ($arFields[$key]["TYPE"] == "int")
						{
							if ((intval($val) == 0) && (mb_strpos($strOperation, "=") !== false))
							{
								$arSqlSearch_tmp[] = "(" . $arFields[$key]["FIELD"] . " IS " . (($strNegative == "Y") ? "NOT " : "") . "NULL) " . (($strNegative == "Y") ? "AND" : "OR") . " " . (($strNegative == "Y") ? "NOT " : "") . "(" . $arFields[$key]["FIELD"] . " " . $strOperation . " 0)";
							}
							else
							{
								$arSqlSearch_tmp[] = (($strNegative == "Y") ? " " . $arFields[$key]["FIELD"] . " IS NULL OR NOT " : "") . "(" . $arFields[$key]["FIELD"] . " " . $strOperation . " " . intval($val) . " )";
							}
						}
						elseif ($arFields[$key]["TYPE"] == "double")
						{
							$val = str_replace(",", ".", $val);

							if ((DoubleVal($val) == 0) && (mb_strpos($strOperation, "=") !== false))
							{
								$arSqlSearch_tmp[] = "(" . $arFields[$key]["FIELD"] . " IS " . (($strNegative == "Y") ? "NOT " : "") . "NULL) " . (($strNegative == "Y") ? "AND" : "OR") . " " . (($strNegative == "Y") ? "NOT " : "") . "(" . $arFields[$key]["FIELD"] . " " . $strOperation . " 0)";
							}
							else
							{
								$arSqlSearch_tmp[] = (($strNegative == "Y") ? " " . $arFields[$key]["FIELD"] . " IS NULL OR NOT " : "") . "(" . $arFields[$key]["FIELD"] . " " . $strOperation . " " . DoubleVal($val) . " )";
							}
						}
						elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
						{
							if ($strOperation == "QUERY")
							{
								$arSqlSearch_tmp[] = GetFilterQuery($arFields[$key]["FIELD"], $val, "Y");
							}
							else
							{
								if (($val == '') && (mb_strpos($strOperation, "=") !== false))
								{
									$arSqlSearch_tmp[] = "(" . $arFields[$key]["FIELD"] . " IS " . (($strNegative == "Y") ? "NOT " : "") . "NULL) " . (($strNegative == "Y") ? "AND NOT" : "OR") . " (" . $DB->Length($arFields[$key]["FIELD"]) . " <= 0) " . (($strNegative == "Y") ? "AND NOT" : "OR") . " (" . $arFields[$key]["FIELD"] . " " . $strOperation . " '" . $DB->ForSql($val) . "' )";
								}
								else
								{
									$arSqlSearch_tmp[] = (($strNegative == "Y") ? " " . $arFields[$key]["FIELD"] . " IS NULL OR NOT " : "") . "(" . $arFields[$key]["FIELD"] . " " . $strOperation . " '" . $DB->ForSql($val) . "' )";
								}
							}
						}
						elseif ($arFields[$key]["TYPE"] == "datetime")
						{
							if ($val == '')
							{
								$arSqlSearch_tmp[] = ($strNegative == "Y" ? "NOT" : "") . "(" . $arFields[$key]["FIELD"] . " IS NULL)";
							}
							else
							{
								$arSqlSearch_tmp[] = ($strNegative == "Y" ? " " . $arFields[$key]["FIELD"] . " IS NULL OR NOT " : "") . "(" . $arFields[$key]["FIELD"] . " " . $strOperation . " " . $DB->CharToDateFunction($DB->ForSql($val), "FULL") . ")";
							}
						}
						elseif ($arFields[$key]["TYPE"] == "date")
						{
							if ($val == '')
							{
								$arSqlSearch_tmp[] = ($strNegative == "Y" ? "NOT" : "") . "(" . $arFields[$key]["FIELD"] . " IS NULL)";
							}
							else
							{
								$arSqlSearch_tmp[] = ($strNegative == "Y" ? " " . $arFields[$key]["FIELD"] . " IS NULL OR NOT " : "") . "(" . $arFields[$key]["FIELD"] . " " . $strOperation . " " . $DB->CharToDateFunction($DB->ForSql($val), "SHORT") . ")";
							}
						}
					}
				}

				if (isset($arFields[$key]["FROM"])
					&& $arFields[$key]["FROM"] <> ''
					&& !in_array($arFields[$key]["FROM"], $arAlreadyJoined))
				{
					if ($strSqlFrom <> '')
					{
						$strSqlFrom .= " ";
					}
					$strSqlFrom .= $arFields[$key]["FROM"];
					$arAlreadyJoined[] = $arFields[$key]["FROM"];
				}

				$strSqlSearch_tmp = "";
				for ($j = 0; $j < count($arSqlSearch_tmp); $j++)
				{
					if ($j > 0)
					{
						$strSqlSearch_tmp .= ($strNegative == "Y" ? " AND " : " OR ");
					}
					$strSqlSearch_tmp .= "(" . $arSqlSearch_tmp[$j] . ")";
				}
				if ($strOrNull == "Y")
				{
					if ($strSqlSearch_tmp <> '')
					{
						$strSqlSearch_tmp .= ($strNegative == "Y" ? " AND " : " OR ");
					}
					$strSqlSearch_tmp .= "(" . $arFields[$key]["FIELD"] . " IS " . ($strNegative == "Y" ? "NOT " : "") . "NULL)";

					if ($strSqlSearch_tmp <> '')
					{
						$strSqlSearch_tmp .= ($strNegative == "Y" ? " AND " : " OR ");
					}
					if ($arFields[$key]["TYPE"] == "int" || $arFields[$key]["TYPE"] == "double")
					{
						$strSqlSearch_tmp .= "(" . $arFields[$key]["FIELD"] . " " . ($strNegative == "Y" ? "<>" : "=") . " 0)";
					}
					elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
					{
						$strSqlSearch_tmp .= "(" . $arFields[$key]["FIELD"] . " " . ($strNegative == "Y" ? "<>" : "=") . " '')";
					}
					else
					{
						$strSqlSearch_tmp .= ($strNegative == "Y" ? " (1=1) " : " (1=0) ");
					}
				}

				if ($strSqlSearch_tmp != "")
				{
					$arSqlSearch[] = "(" . $strSqlSearch_tmp . ")";
				}
			}
		}

		for ($i = 0; $i < count($arSqlSearch); $i++)
		{
			if ($strSqlWhere <> '')
			{
				$strSqlWhere .= " AND ";
			}
			$strSqlWhere .= "(" . $arSqlSearch[$i] . ")";
		}
		// <-- WHERE

		// ORDER BY -->
		$arSqlOrder = [];
		foreach ($arOrder as $by => $order)
		{
			$by = mb_strtoupper($by);
			$order = mb_strtoupper($order);

			if ($order != "ASC")
			{
				$order = "DESC";
			}
			else
			{
				$order = "ASC";
			}

			if (array_key_exists($by, $arFields))
			{
				$arSqlOrder[] = " " . $arFields[$by]["FIELD"] . " " . $order . " ";

				if (isset($arFields[$by]["FROM"])
					&& $arFields[$by]["FROM"] <> ''
					&& !in_array($arFields[$by]["FROM"], $arAlreadyJoined))
				{
					if ($strSqlFrom <> '')
					{
						$strSqlFrom .= " ";
					}
					$strSqlFrom .= $arFields[$by]["FROM"];
					$arAlreadyJoined[] = $arFields[$by]["FROM"];
				}
			}
			elseif ($obUserFieldsSql)
			{
				$arSqlOrder[] = " " . $obUserFieldsSql->GetOrder($by) . " " . $order . " ";
			}
		}

		$strSqlOrderBy = "";
		DelDuplicateSort($arSqlOrder);
		for ($i = 0; $i < count($arSqlOrder); $i++)
		{
			if ($strSqlOrderBy <> '')
			{
				$strSqlOrderBy .= ", ";
			}

			if ($DB->type == "ORACLE")
			{
				if (mb_substr($arSqlOrder[$i], -3) == "ASC")
				{
					$strSqlOrderBy .= $arSqlOrder[$i] . " NULLS FIRST";
				}
				else
				{
					$strSqlOrderBy .= $arSqlOrder[$i] . " NULLS LAST";
				}
			}
			else
			{
				$strSqlOrderBy .= $arSqlOrder[$i];
			}
		}
		// <-- ORDER BY

		return [
			"SELECT" => $strSqlSelect,
			"FROM" => $strSqlFrom,
			"WHERE" => $strSqlWhere,
			"GROUPBY" => $strSqlGroupBy,
			"ORDERBY" => $strSqlOrderBy,
		];
	}

	/**
	 * @param $arFields
	 * @deprecated Use (new \Bitrix\Timeman\UseCase\Worktime\Manage\Approve\Handler())->handle($worktimeForm);
	 */
	public static function Approve($ID, $check_rights = true)
	{
		if ($check_rights)
		{
			$hasAccess = false;

			$arAccessUsers = CTimeMan::GetAccess();
			if (count($arAccessUsers['WRITE']) > 0)
			{
				$bCanEditAll = in_array('*', $arAccessUsers['WRITE']);

				$dbRes = CTimeManEntry::GetList(
					[],
					['ID' => $ID],
					false, false, ['*']
				);

				$arRes = $dbRes->Fetch();
				if ($arRes)
				{
					$hasAccess = ($bCanEditAll || in_array($arRes['USER_ID'], $arAccessUsers['WRITE']));
				}
			}

			if (!$hasAccess)
			{
				$GLOBALS['APPLICATION']->ThrowException('Access denied');
				return false;
			}
		}

		if (CTimeManEntry::Update($ID, ['ACTIVE' => 'Y']))
		{
			CTimeManReport::Approve($ID);
			CTimeManReportDaily::SetActive($ID);

			CTimeManNotify::SendMessage($ID, 'U');

			return true;
		}

		return false;
	}

	public static function GetNeighbours($ENTRY_ID, $USER_ID, $bCheckActive = false)
	{
		global $DB;

		$ENTRY_ID = intval($ENTRY_ID);
		$USER_ID = intval($USER_ID);

		$res = [];

		if ($ENTRY_ID > 0 && $USER_ID > 0)
		{
			$arFilter = [
				'<ID' => $ENTRY_ID,
				'USER_ID' => $USER_ID,
			];

			if ($bCheckActive)
			{
				$arFilter['INACTIVE_OR_ACTIVATED'] = 'Y';
			}

			$dbRes = CTimeManEntry::GetList(['ID' => 'DESC'], $arFilter, false, ['nTopCount' => 1], ['ID']);
			if ($arRes = $dbRes->Fetch())
			{
				$res['PREV'] = $arRes['ID'];
			}

			$arFilter['>ID'] = $arFilter['<ID'];
			unset($arFilter['<ID']);

			$dbRes = CTimeManEntry::GetList(['ID' => 'ASC'], $arFilter, false, ['nTopCount' => 1], ['ID']);
			if ($arRes = $dbRes->Fetch())
			{
				$res['NEXT'] = $arRes['ID'];
			}
		}

		return $res;
	}

	private static function correctDuration(int $duration): int
	{
		$secondsPerDay = 86400;

		if ($duration < 0)
		{
			return $duration + $secondsPerDay;
		}

		return $duration;
	}
}
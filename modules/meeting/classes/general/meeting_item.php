<?
class CAllMeetingItem
{
	public static function Add($arFields, $bSkipInstanceAdd = false)
	{
		global $DB;

		foreach(GetModuleEvents('meeting', 'OnBeforeMeetingItemAdd', true) as $a)
		{
			if (ExecuteModuleEventEx($a, array(&$arFields)) === false)
			{
				return false;
			}
		}

		if (!self::CheckFields('ADD', $arFields))
			return false;

		$ID = $DB->Add('b_meeting_item', $arFields, array('DESCRIPTION'));
		if ($ID > 0)
		{
			$arFields['ID'] = $ID;

			if (isset($arFields['FILES']))
			{
				self::SetFiles($ID, $arFields['FILES']);
			}

			foreach(GetModuleEvents('meeting', 'OnAfterMeetingItemAdd', true) as $a)
			{
				ExecuteModuleEventEx($a, array($arFields));
			}

			if (!$bSkipInstanceAdd && $arFields['MEETING_ID'])
			{
				$arFields['ITEM_ID'] = $arFields['ID'];
				unset($arFields['ID']);

				CMeetingInstance::Add($arFields);
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

		foreach(GetModuleEvents('meeting', 'OnBeforeMeetingItemUpdate', true) as $a)
		{
			if(ExecuteModuleEventEx($a, array(&$arFields)) === false)
			{
				return false;
			}
		}


		if (!self::CheckFields('UPDATE', $arFields))
			return false;

		$strUpdate = $DB->PrepareUpdate('b_meeting_item', $arFields);

		$dbRes = null;

		$bNeedUpdate = strlen($strUpdate) > 0;
		if ($bNeedUpdate)
		{
			$arBind = array();
			if(isset($arFields['DESCRIPTION']))
			{
				$arBind['DESCRIPTION'] = $arFields['DESCRIPTION'];
			}

			$query = 'UPDATE b_meeting_item SET '.$strUpdate.' WHERE ID=\''.intval($ID).'\'';
			$dbRes = $DB->QueryBind($query, $arBind);
		}

		if (!$bNeedUpdate || $dbRes)
		{
			if (isset($arFields['FILES']))
			{
				self::SetFiles($ID, $arFields['FILES']);
			}

			if (isset($arFields['TASKS']))
			{
				self::SetTasks($ID, $arFields['TASKS']);
			}

			foreach(GetModuleEvents('meeting', 'OnAfterMeetingItemUpdate', true) as $a)
			{
				ExecuteModuleEventEx($a, array($ID, $arFields));
			}

			return $ID;
		}

		return false;
	}

	public static function Delete($ID)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID < 1)
			return false;

		foreach(GetModuleEvents('meeting', 'OnBeforeMeetingItemDelete', true) as $a)
		{
			if(ExecuteModuleEventEx($a, array($ID)) === false)
			{
				return false;
			}
		}

		CMeetingInstance::DeleteByItemID($ID);
		if ($DB->Query("DELETE FROM b_meeting_item WHERE ID='".$ID."'"))
		{
			foreach(GetModuleEvents('meeting', 'OnAfterMeetingItemDelete', true) as $a)
			{
				ExecuteModuleEventEx($a, array($ID));
			}

			return true;
		}

		return false;
	}

	public static function DeleteAbandoned()
	{
		global $DB;
		$dbRes = $DB->Query('SELECT I.ID FROM b_meeting_item I LEFT JOIN b_meeting_instance MI ON MI.ITEM_ID=I.ID GROUP BY I.ID HAVING COUNT(MI.ID)=0');

		$ids = '';
		while ($arRes = $dbRes->Fetch())
			$ids .= ($ids == '' ? '' : ',').$arRes['ID'];

		if ($ids != '')
		{
			$GLOBALS['DB']->Query('DELETE FROM b_meeting_item_files WHERE ITEM_ID IN ('.$ids.')');
			$GLOBALS['DB']->Query('DELETE FROM b_meeting_item_tasks WHERE ITEM_ID IN ('.$ids.')');
			$GLOBALS['DB']->Query('DELETE FROM b_meeting_item WHERE ID IN ('.$ids.')');
		}
	}

	public static function IsEditable($ID)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID <= 0)
			return false;

		$dbRes = $DB->Query("SELECT COUNT(ID) CNT FROM b_meeting_instance WHERE ITEM_ID='".$ID."'");
		$arRes = $dbRes->Fetch();
		if ($arRes)
		{
			return $arRes['CNT'] == 1;
		}

		return false;
	}

	public static function HasAccess($ID, $USER_ID = null)
	{
		if ($USER_ID === null)
			$USER_ID = $GLOBALS['USER']->GetID();
		else
			$USER_ID = intval($USER_ID);

		$ID = intval($ID);
		if ($ID > 0 && $USER_ID > 0)
		{
			$query = "
SELECT 1 FROM b_meeting_item bmi
LEFT JOIN b_meeting_instance bmii ON bmi.ID=bmii.ITEM_ID
LEFT JOIN b_meeting_users bmu ON bmu.MEETING_ID=bmii.MEETING_ID
WHERE bmi.ID='".$ID."' AND bmu.USER_ID='".$USER_ID."'
";

			$dbRes = $GLOBALS['DB']->Query($query);
			if ($dbRes->Fetch())
				return true;
		}

		return false;
	}

	public static function SetFiles($ID, $arFiles, $src = null)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID <= 0)
			return;

		if (count($arFiles) <= 0)
		{
			$DB->Query("DELETE FROM b_meeting_item_files WHERE ITEM_ID='".intval($ID)."'");
		}

		if (count($arFiles) > 0)
		{
			foreach ($arFiles as $FILE_ID)
			{
				$FILE_ID = intval($FILE_ID);
				if ($FILE_ID > 0)
				{
					$DB->Query("INSERT INTO b_meeting_item_files (ITEM_ID, FILE_ID, FILE_SRC) VALUES ('".$ID."', '".$FILE_ID."', '".intval($src)."')", true);
				}
			}
		}
	}

	public static function GetFiles($ID, $fileId = null)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID <= 0)
			return;

		$query = "SELECT FILE_ID, FILE_SRC FROM b_meeting_item_files WHERE ITEM_ID='".$ID."'";

		if ($fileId > 0)
		{
			$query .= " AND FILE_ID='".intval($fileId)."'";
		}

		$query .= " ORDER BY FILE_ID ASC";


		return $DB->Query($query);
	}

	public static function AddTask($ID, $TASK_ID)
	{
		global $DB;

		$ID = intval($ID); $TASK_ID = intval($TASK_ID);

		if ($ID > 0 || $TASK_ID > 0)
		{
			$DB->Query("INSERT INTO b_meeting_item_tasks (ITEM_ID, TASK_ID) VALUES ('".$ID."', '".$TASK_ID."')", true);
		}
	}

	public static function SetTasks($ID, $arTasks = array())
	{
		global $DB;

		$ID = intval($ID);
		if ($ID <= 0)
			return;

		$DB->Query("DELETE FROM b_meeting_item_tasks WHERE ITEM_ID='".$ID."'");

		if (is_array($arTasks))
		{
			foreach ($arTasks as $TASK_ID)
			{
				$TASK_ID = intval($TASK_ID);
				if ($TASK_ID > 0)
				{
					$DB->Query("INSERT INTO b_meeting_item_tasks (ITEM_ID, TASK_ID) VALUES ('".$ID."', '".$TASK_ID."')");
				}
			}
		}
	}

	public static function GetTasks($ID)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID <= 0)
			return;

		$arRes = array();
		$dbRes = $DB->Query("SELECT TASK_ID FROM b_meeting_item_tasks WHERE ITEM_ID='".$ID."' ORDER BY TASK_ID ASC");
		while ($arT = $dbRes->Fetch())
		{
			$arRes[] = $arT['TASK_ID'];
		}
		return $arRes;
	}

	public static function GetTasksCount($ID, $INSTANCE_ID = 0)
	{
		global $DB;

		$ID = intval($ID);
		$INSTANCE_ID = intval($INSTANCE_ID);

		if ($ID <= 0)
			return;

		if ($INSTANCE_ID > 0)
		{
			$strSql = "
SELECT COUNT(TASK_ID) AS CNT, INSTANCE_ID
FROM b_meeting_item_tasks
WHERE INSTANCE_ID='".$INSTANCE_ID."' OR INSTANCE_ID IS NULL AND ITEM_ID='".$ID."'
GROUP BY INSTANCE_ID
";

			$dbRes = $DB->Query($strSql);
			$arResult = array(0 => 0, 1 => 0);
			while ($arRes = $dbRes->Fetch())
			{
				$arResult[$arRes['INSTANCE_ID'] > 0 ? 1 : 0] = $arRes['CNT'];
			}
			$arResult[0] += $arResult[1];
			return $arResult;
		}
		else
		{
			$strSql = "SELECT COUNT(TASK_ID) AS CNT FROM b_meeting_item_tasks WHERE ITEM_ID='".$ID."'";
			$dbRes = $DB->Query($strSql);
			if ($arRes = $dbRes->Fetch())
				return $arRes['CNT'];
		}
	}

	public static function DeleteFiles($ID)
	{
		$dbFiles = self::GetFiles($ID);
		while ($arRes = $dbFiles->Fetch())
		{
			CFile::Delete($arRes['FILE_ID']);
		}
		self::SetFiles($ID, array());
	}

	public static function DeleteFilesBySrc($FILE_SRC)
	{
		global $DB;

		$FILE_SRC = intval($FILE_SRC);
		if ($FILE_SRC > 0)
		{
			$dbRes = $DB->Query("SELECT * FROM b_meeting_item_files WHERE FILE_SRC='".$FILE_SRC."'");
			while ($arRes = $dbRes->Fetch())
				CFile::Delete($arRes['FILE_ID']);
			$DB->Query("DELETE FROM b_meeting_item_files WHERE FILE_SRC='".$FILE_SRC."'");
		}
	}

	protected static function CheckFields($action, &$arFields)
	{
		unset($arFields['ID']);
		return true;
	}

	protected static function GetFilterOperation($key)
	{
		$strNegative = "N";
		if (substr($key, 0, 1)=="!")
		{
			$key = substr($key, 1);
			$strNegative = "Y";
		}

		$strOrNull = "N";
		if (substr($key, 0, 1)=="+")
		{
			$key = substr($key, 1);
			$strOrNull = "Y";
		}

		if (substr($key, 0, 2)==">=")
		{
			$key = substr($key, 2);
			$strOperation = ">=";
		}
		elseif (substr($key, 0, 1)==">")
		{
			$key = substr($key, 1);
			$strOperation = ">";
		}
		elseif (substr($key, 0, 2)=="<=")
		{
			$key = substr($key, 2);
			$strOperation = "<=";
		}
		elseif (substr($key, 0, 1)=="<")
		{
			$key = substr($key, 1);
			$strOperation = "<";
		}
		elseif (substr($key, 0, 1)=="@")
		{
			$key = substr($key, 1);
			$strOperation = "IN";
		}
		elseif (substr($key, 0, 1)=="~")
		{
			$key = substr($key, 1);
			$strOperation = "LIKE";
		}
		elseif (substr($key, 0, 1)=="%")
		{
			$key = substr($key, 1);
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
				$ci = count($arFieldsKeys);
				for ($i = 0; $i < $ci; $i++)
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
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i]."_X1, ";

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD"], "FULL")." as ".$arFieldsKeys[$i];
					}
					elseif ($arFields[$arFieldsKeys[$i]]["TYPE"] == "date")
					{
						if ((strtoupper($DB->type)=="ORACLE" || strtoupper($DB->type)=="MSSQL") && (array_key_exists($arFieldsKeys[$i], $arOrder)))
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i]."_X1, ";

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
									$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val."_X1, ";

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "FULL")." as ".$val;
							}
							elseif ($arFields[$val]["TYPE"] == "date")
							{
								if ((strtoupper($DB->type)=="ORACLE" || strtoupper($DB->type)=="MSSQL") && (array_key_exists($val, $arOrder)))
									$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val."_X1, ";

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

		$ci = count($filter_keys);
		for ($i = 0; $i < $ci; $i++)
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
				$cj = count($vals);
				for ($j = 0; $j < $cj; $j++)
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
				$cj = count($arSqlSearch_tmp);
				for ($j = 0; $j < $cj; $j++)
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

					if (strlen($strSqlSearch_tmp) > 0)
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

		$ci = count($arSqlSearch);
		for ($i = 0; $i < $ci; $i++)
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
			else
				$order = "ASC";

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
		DelDuplicateSort($arSqlOrder);
		$ci = count($arSqlOrder);
		for ($i=0; $i<$ci; $i++)
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
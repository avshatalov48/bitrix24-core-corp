<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 *
 * @deprecated
 */


class CTaskReport
{
	function GetList($arOrder=array(), $arFilter=array(), $arNavParams = array())
	{
		global $DB;

		$obUserFieldsSqlDepartment = new CUserTypeSQL;
		$obUserFieldsSqlDepartment->SetEntity("USER", "T.RESPONSIBLE_ID");
		$obUserFieldsSqlDepartment->SetSelect(array("UF_DEPARTMENT"));

		if (!$arFilter["RESPONSIBLE_ID"])
		{
			$arFilter["SUBORDINATE_TASKS"] = "Y";
		}

		$arSqlSearch = CTasks::GetFilter($arFilter);

		$IBlockID = COption::GetOptionInt('intranet', 'iblock_structure');

		$strFrom = " FROM
				b_tasks T
			INNER JOIN
				b_user U ON U.ID = T.RESPONSIBLE_ID AND U.ACTIVE = 'Y'
			INNER JOIN
				b_utm_user BUF1 ON BUF1.FIELD_ID = " . (int) $obUserFieldsSqlDepartment->user_fields["UF_DEPARTMENT"]["ID"] . " AND BUF1.VALUE_ID = T.RESPONSIBLE_ID
			INNER JOIN
				b_iblock_section IBS ON IBS.ID = BUF1.VALUE_INT
			LEFT JOIN
				b_uts_iblock_".$IBlockID."_section BUF2 ON BUF2.VALUE_ID = IBS.ID
			WHERE
				".implode(" AND ", $arSqlSearch)."
			GROUP BY 
				T.RESPONSIBLE_ID,
				U.NAME,
				U.SECOND_NAME,
				U.LAST_NAME,
				U.LOGIN,
				BUF1.VALUE_INT,
				IBS.LEFT_MARGIN,
				IBS.RIGHT_MARGIN,
				IBS.NAME,
				CASE WHEN BUF2.UF_HEAD = T.RESPONSIBLE_ID THEN 1 ELSE 0 END";

		$strSql = "
			SELECT
				T.RESPONSIBLE_ID,
				U.NAME AS NAME,
				U.LAST_NAME AS LAST_NAME,
				U.SECOND_NAME AS SECOND_NAME,
				U.LOGIN AS LOGIN,
				COUNT(T.RESPONSIBLE_ID) AS CNT,
				SUM(CASE WHEN T.ADD_IN_REPORT = 'Y' THEN 1 ELSE 0 END) AS IN_REPORT,
				SUM(".CTaskReport::GetPeriodCondition($arFilter["PERIOD"], "CREATED_DATE").") AS NEW,
				SUM(".CTaskReport::GetPeriodCondition($arFilter["PERIOD"], "CREATED_DATE", "T.ADD_IN_REPORT = 'Y'").") AS NEW_IN_REPORT,
				SUM(".CTaskReport::GetPeriodCondition($arFilter["PERIOD"], "CLOSED_DATE", "T.CLOSED_DATE IS NOT NULL").") AS CLOSED,
				SUM(".CTaskReport::GetPeriodCondition($arFilter["PERIOD"], "CLOSED_DATE", "T.CLOSED_DATE IS NOT NULL AND T.ADD_IN_REPORT = 'Y'").") AS CLOSED_IN_REPORT,
				SUM(".CTaskReport::GetPeriodCondition($arFilter["PERIOD"], "CLOSED_DATE", "T.CLOSED_DATE IS NOT NULL AND T.DEADLINE IS NOT NULL AND T.DEADLINE < T.CLOSED_DATE").") AS OVERDUE,
				SUM(".CTaskReport::GetPeriodCondition($arFilter["PERIOD"], "CLOSED_DATE", "T.CLOSED_DATE IS NOT NULL AND T.DEADLINE IS NOT NULL AND T.DEADLINE < T.CLOSED_DATE AND T.ADD_IN_REPORT = 'Y'").") AS OVERDUE_IN_REPORT,
				SUM(".CTaskReport::GetPeriodCondition($arFilter["PERIOD"], "CLOSED_DATE", "T.CLOSED_DATE IS NOT NULL AND T.ADD_IN_REPORT = 'Y' AND (T.MARK = 'P' OR T.MARK = 'N')").") AS MARKED_IN_REPORT,
				SUM(".CTaskReport::GetPeriodCondition($arFilter["PERIOD"], "CLOSED_DATE", "T.CLOSED_DATE IS NOT NULL AND T.ADD_IN_REPORT = 'Y' AND T.MARK = 'P'").") AS POSITIVE,
				BUF1.VALUE_INT AS DEPARTMENT_ID,
				IBS.LEFT_MARGIN AS LEFT_MARGIN,
				IBS.RIGHT_MARGIN AS RIGHT_MARGIN,
				IBS.NAME AS DEPARTMENT_NAME,
				CASE WHEN BUF2.UF_HEAD = T.RESPONSIBLE_ID THEN 1 ELSE 0 END AS IS_HEAD
			" . $strFrom;

		if (!is_array($arOrder))
			$arOrder = array();

		foreach ($arOrder as $by => $order)
		{
			$by = strtolower($by);
			$order = strtolower($order);
			if ($order != "asc")
				$order = "desc";

			if ($by == "responsible")
				$arSqlOrder[] = " LAST_NAME ".$order.", NAME ".$order." ";
			elseif ($by == "new")
				$arSqlOrder[] = " NEW_IN_REPORT ".$order." ";
			elseif ($by == "open")
				$arSqlOrder[] = " IN_REPORT ".$order." ";
			elseif ($by == "closed")
				$arSqlOrder[] = " CLOSED_IN_REPORT ".$order." ";
			elseif ($by == "overdued")
				$arSqlOrder[] = " OVERDUE_IN_REPORT ".$order." ";
			elseif ($by == "marked")
				$arSqlOrder[] = " MARKED_IN_REPORT ".$order." ";
			elseif ($by == "positive")
				$arSqlOrder[] = " POSITIVE ".$order." ";
		}

		$strSqlOrder = "";
		array_unshift($arSqlOrder, " IS_HEAD DESC ");
		array_unshift($arSqlOrder, " LEFT_MARGIN ASC ");
		DelDuplicateSort($arSqlOrder);
		$arSqlOrderCnt = count($arSqlOrder);
		for ($i = 0; $i < $arSqlOrderCnt; $i++)
		{
			if ($i == 0)
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ",";

			$strSqlOrder .= $arSqlOrder[$i];
		}

		$strSql .= $strSqlOrder;

		if (isset($arNavParams["NAV_PARAMS"]) && is_array($arNavParams["NAV_PARAMS"]))
		{
			$nTopCount = (int) $arNavParams['NAV_PARAMS']['nTopCount'];

			if ($nTopCount > 0)
			{
				$strSql = $DB->TopSql($strSql, $nTopCount);
				$res = $DB->Query($strSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);
			}
			else
			{
				$res_cnt = $DB->Query(
					"SELECT COUNT(*) as C
					FROM (
						SELECT T.RESPONSIBLE_ID,
							U.NAME AS NAME,
							U.LAST_NAME AS LAST_NAME,
							U.SECOND_NAME AS SECOND_NAME,
							U.LOGIN AS LOGIN, 
							BUF1.VALUE_INT AS DEPARTMENT_ID,
							IBS.LEFT_MARGIN AS LEFT_MARGIN,
							IBS.RIGHT_MARGIN AS RIGHT_MARGIN,
							IBS.NAME AS DEPARTMENT_NAME,
							CASE WHEN BUF2.UF_HEAD = T.RESPONSIBLE_ID THEN 1 ELSE 0 END AS IS_HEAD
						" . $strFrom . "
					) TMPT"
				);
				$res_cnt = $res_cnt->Fetch();
				$res = new CDBResult();
				$res->NavQuery($strSql, $res_cnt["C"], $arNavParams["NAV_PARAMS"]);
			}
		}
		else
			$res = $DB->Query($strSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);

		return $res;
	}


	function GetDepartementStats($arFilter=array())
	{
		global $DB;

		$obUserFieldsSqlDepartment = new CUserTypeSQL;
		$obUserFieldsSqlDepartment->SetEntity("USER", "T.RESPONSIBLE_ID");
		$obUserFieldsSqlDepartment->SetSelect(array("UF_DEPARTMENT"));

		if (!$arFilter["RESPONSIBLE_ID"])
		{
			$arFilter["SUBORDINATE_TASKS"] = "Y";
		}

		$arSqlSearch = CTasks::GetFilter($arFilter);

		$strSql = "
			SELECT
				COUNT(T.RESPONSIBLE_ID) AS CNT,
				SUM(CASE WHEN T.ADD_IN_REPORT = 'Y' THEN 1 ELSE 0 END) AS IN_REPORT,
				SUM(".CTaskReport::GetPeriodCondition($arFilter["PERIOD"], "CREATED_DATE").") AS NEW,
				SUM(".CTaskReport::GetPeriodCondition($arFilter["PERIOD"], "CREATED_DATE", "T.ADD_IN_REPORT = 'Y'").") AS NEW_IN_REPORT,
				SUM(".CTaskReport::GetPeriodCondition($arFilter["PERIOD"], "CLOSED_DATE", "T.CLOSED_DATE IS NOT NULL").") AS CLOSED,
				SUM(".CTaskReport::GetPeriodCondition($arFilter["PERIOD"], "CLOSED_DATE", "T.CLOSED_DATE IS NOT NULL AND T.ADD_IN_REPORT = 'Y'").") AS CLOSED_IN_REPORT,
				SUM(".CTaskReport::GetPeriodCondition($arFilter["PERIOD"], "CLOSED_DATE", "T.CLOSED_DATE IS NOT NULL AND T.DEADLINE IS NOT NULL AND T.DEADLINE < T.CLOSED_DATE").") AS OVERDUE,
				SUM(".CTaskReport::GetPeriodCondition($arFilter["PERIOD"], "CLOSED_DATE", "T.CLOSED_DATE IS NOT NULL AND T.DEADLINE IS NOT NULL AND T.DEADLINE < T.CLOSED_DATE AND T.ADD_IN_REPORT = 'Y'").") AS OVERDUE_IN_REPORT,
				SUM(".CTaskReport::GetPeriodCondition($arFilter["PERIOD"], "CLOSED_DATE", "T.CLOSED_DATE IS NOT NULL AND T.ADD_IN_REPORT = 'Y' AND (T.MARK = 'P' OR T.MARK = 'N')").") AS MARKED_IN_REPORT,
				SUM(".CTaskReport::GetPeriodCondition($arFilter["PERIOD"], "CLOSED_DATE", "T.CLOSED_DATE IS NOT NULL AND T.ADD_IN_REPORT = 'Y' AND T.MARK = 'P'").") AS POSITIVE,
				BUF1.VALUE_INT AS DEPARTMENT_ID
			FROM
				b_tasks T
			INNER JOIN
				b_user U ON U.ID = T.RESPONSIBLE_ID AND U.ACTIVE = 'Y'
			INNER JOIN
				b_utm_user BUF1 ON BUF1.FIELD_ID = ".$obUserFieldsSqlDepartment->user_fields["UF_DEPARTMENT"]["ID"]." AND BUF1.VALUE_ID = T.RESPONSIBLE_ID
			WHERE
				".implode(" AND ", $arSqlSearch)."
			GROUP BY
				BUF1.VALUE_INT
		";

		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $res;
	}


	function GetCompanyStats($arFilter=array())
	{
		global $DB;

		$arSqlSearch = CTasks::GetFilter($arFilter);

		if ( ! array_key_exists('PERIOD', $arFilter) )
			$arFilter['PERIOD'] = null;

		$strSql = "
			SELECT
				COUNT(DISTINCT T.RESPONSIBLE_ID) AS RESPONSIBLES,
				SUM(".CTaskReport::GetPeriodCondition($arFilter["PERIOD"], "CLOSED_DATE", "T.CLOSED_DATE IS NOT NULL AND T.ADD_IN_REPORT = 'Y' AND (T.MARK = 'P' OR T.MARK = 'N')").") AS MARKED_IN_REPORT,
				SUM(".CTaskReport::GetPeriodCondition($arFilter["PERIOD"], "CLOSED_DATE", "T.CLOSED_DATE IS NOT NULL AND T.ADD_IN_REPORT = 'Y' AND T.MARK = 'P'").") AS POSITIVE
			FROM
				b_tasks T
			INNER JOIN
				b_user U ON U.ID = T.RESPONSIBLE_ID
			WHERE
				T.ADD_IN_REPORT = 'Y'
			AND
				U.ACTIVE = 'Y'
			";

		if (count($arSqlSearch) !== 0)
			$strSql .= ' AND ' . implode(' AND ', $arSqlSearch);

		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $res;
	}


	function GetEmployeesCount()
	{
		global $DB;

		$obUserFieldsSqlDepartment = new CUserTypeSQL;
		$obUserFieldsSqlDepartment->SetEntity("USER", "U.ID");
		$obUserFieldsSqlDepartment->SetSelect(array("UF_DEPARTMENT"));
		$obUserFieldsSqlDepartment->SetFilter(array("!UF_DEPARTMENT" => false));

		$strFilter = $obUserFieldsSqlDepartment->GetFilter();
		$strJoin = $obUserFieldsSqlDepartment->GetJoin("U.ID");

		$strSql = "
			SELECT
				COUNT(DISTINCT U.ID) AS CNT
			FROM
				b_user U
			LEFT JOIN
				b_utm_user BUF1 ON BUF1.FIELD_ID = ".$obUserFieldsSqlDepartment->user_fields["UF_DEPARTMENT"]["ID"]." AND BUF1.VALUE_ID = U.ID
			WHERE
				U.ACTIVE = 'Y'
			AND
				BUF1.VALUE_INT IS NOT NULL AND BUF1.VALUE_INT <> 0
		";
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $res->Fetch())
		{
			return $arRes["CNT"];
		}

		return 0;
	}


	function GetPeriodCondition($arPeriod, $field, $extraCond = "1=1")
	{
		global $DB;
		if ($arPeriod["START"])
		{
			$arPeriod["START"] = CDatabase::FormatDate($arPeriod["START"], FORMAT_DATETIME);
		}
		if ($arPeriod["END"])
		{
			$arPeriod["END"] =  CDatabase::FormatDate($arPeriod["END"], FORMAT_DATETIME);
		}
		$condition = "CASE WHEN ".
			($arPeriod["START"] || $arPeriod["END"] ?
				($arPeriod["START"] ? "T.".$field." >= ".$DB->CharToDateFunction($arPeriod["START"]) : "").
				($arPeriod["START"] && $arPeriod["END"] ? " AND " : "").
				($arPeriod["END"] ? "T.".$field." <= ".$DB->CharToDateFunction($arPeriod["END"]) : "").
			" AND " :
			"").
			$extraCond." THEN 1 ELSE 0 END";

		return $condition;
	}
}
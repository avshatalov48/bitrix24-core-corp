<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$sql_str = "
SELECT T.ID
FROM b_task T
WHERE T.MODULE_ID='timeman'";
$r = $DB->Query($sql_str, false, "File: ".__FILE__."<br>Line: ".__LINE__);
$arIds = Array();
while($arR = $r->Fetch())
	$arIds[] = $arR['ID'];

if (count($arIds)>0)
{
	$strTaskIds = implode(",", $arIds);

	$sql_str = "DELETE FROM b_group_task
			WHERE TASK_ID IN (".$strTaskIds.")";

	$r = $DB->Query($sql_str, false, "File: ".__FILE__."<br>Line: ".__LINE__);

	$sql_str = "DELETE FROM b_task_operation
			WHERE TASK_ID IN (".$strTaskIds.")";
	$r = $DB->Query($sql_str, false, "File: ".__FILE__."<br>Line: ".__LINE__);

	$sql_str = "DELETE FROM b_operation WHERE MODULE_ID='timeman'";
	$DB->Query($sql_str, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

	$sql_str = "DELETE FROM b_task WHERE MODULE_ID='timeman'";
	$DB->Query($sql_str, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
}

global $CACHE_MANAGER;
if(is_object($CACHE_MANAGER))
{
	$CACHE_MANAGER->CleanDir("b_task");
	$CACHE_MANAGER->CleanDir("b_task_operation");
}
?>
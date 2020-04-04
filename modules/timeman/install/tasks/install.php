<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// *******************************************************************************************
// Install new right system: operation and tasks
// *******************************************************************************************
// ############ TIMEMAN MODULE OPERATION ###########
$arFOp = Array();
$arFOp[] = Array('tm_manage', 'timeman', '', 'module');
$arFOp[] = Array('tm_manage_all', 'timeman', '', 'module');
$arFOp[] = Array('tm_read_subordinate', 'timeman', '', 'module');
$arFOp[] = Array('tm_read', 'timeman', '', 'module');
$arFOp[] = Array('tm_write_subordinate', 'timeman', '', 'module');
$arFOp[] = Array('tm_write', 'timeman', '', 'module');
$arFOp[] = Array('tm_settings', 'timeman', '', 'module');


// ############ TIMEMAN MODULE TASKS ###########
$arTasksF = Array();
$arTasksF[] = Array('timeman_denied', 'D', 'timeman', 'Y', '', 'module'); //
$arTasksF[] = Array('timeman_subordinate', 'N', 'timeman', 'Y', '', 'module'); // ordinary employee or department head
$arTasksF[] = Array('timeman_read', 'R', 'timeman', 'Y', '', 'module'); // HR
$arTasksF[] = Array('timeman_write', 'T', 'timeman', 'Y', '', 'module'); // boss
$arTasksF[] = Array('timeman_full_access', 'W', 'timeman', 'Y', '', 'module'); // admin


//Operations in Tasks
$arOInT = Array();

$arOInT['timeman_subordinate'] = Array(
	'tm_manage', 'tm_read_subordinate', 'tm_write_subordinate'
);

$arOInT['timeman_read'] = Array(
	'tm_read', 'tm_write_subordinate',
);

$arOInT['timeman_write'] = Array(
	'tm_read', 'tm_write',
);

$arOInT['timeman_full_access'] = Array(
	'tm_manage',
	'tm_manage_all',
	'tm_read',
	'tm_write',
	'tm_settings',
);

$arDBOperations = array();
$rsOperations = $DB->Query("SELECT NAME FROM b_operation WHERE MODULE_ID = 'timeman'");
while($ar = $rsOperations->Fetch())
	$arDBOperations[$ar["NAME"]] = $ar["NAME"];


foreach($arFOp as $ar)
{
	if(!isset($arDBOperations[$ar[0]]))
	{
		$DB->Query("
			INSERT INTO b_operation
			(NAME,MODULE_ID,DESCRIPTION,BINDING)
			VALUES
			('".$ar[0]."','".$ar[1]."','".$ar[2]."','".$ar[3]."')
		", true, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
	}
}

$arDBTasks = array();
$rsTasks = $DB->Query("SELECT NAME FROM b_task WHERE MODULE_ID = 'timeman' AND SYS = 'Y'");
while($ar = $rsTasks->Fetch())
	$arDBTasks[$ar["NAME"]] = $ar["NAME"];

foreach($arTasksF as $ar)
{
	if(!isset($arDBTasks[$ar[0]]))
	{
		$DB->Query("
			INSERT INTO b_task
			(NAME,LETTER,MODULE_ID,SYS,DESCRIPTION,BINDING)
			VALUES
			('".$ar[0]."','".$ar[1]."','".$ar[2]."','".$ar[3]."','".$ar[4]."','".$ar[5]."')
		", true, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
	}
}

// ############ b_task_operation ###########
foreach($arOInT as $tname => $arOp)
{
	$sql_str = "
		INSERT INTO b_task_operation
		(TASK_ID,OPERATION_ID)
		SELECT T.ID, O.ID
		FROM
			b_task T
			,b_operation O
		WHERE
			T.SYS='Y'
			AND T.NAME='".$tname."'
			AND O.NAME in ('".implode("','", $arOp)."')
			AND O.NAME not in (
				SELECT O2.NAME
				FROM
					b_task T2
					inner join b_task_operation TO2 on TO2.TASK_ID = T2.ID
					inner join b_operation O2 on O2.ID = TO2.OPERATION_ID
				WHERE
					T2.SYS='Y'
					AND T2.NAME='".$tname."'
			)
";
	$z = $DB->Query($sql_str, true, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
}

global $CACHE_MANAGER;
if(is_object($CACHE_MANAGER))
{
	$CACHE_MANAGER->CleanDir("b_task");
	$CACHE_MANAGER->CleanDir("b_task_operation");
}
?>
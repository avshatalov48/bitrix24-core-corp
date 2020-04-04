<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('TASKS_CHANGE_STAGE_NAME'),
	'DESCRIPTION' => GetMessage('TASKS_CHANGE_STAGE_DESC'),
	'TYPE' => array('activity', 'robot_activity'),
	'CLASS' => 'TasksChangeStageActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => array(
		'ID' => 'document',
		"OWN_ID" => 'tasks',
		"OWN_NAME" => GetMessage('TASKS_CHANGE_STAGE_CATEGORY'),
	),
	'FILTER' => array(
		'INCLUDE' => array(
			['tasks']
		),
	),
	'ROBOT_SETTINGS' => array(
		'CATEGORY' => 'employee'
	),
);

if ($documentType[0] === 'tasks' && strpos($documentType[2], 'TASK_USER_') === 0)
{
	$arActivityDescription['EXCLUDED'] = true;
}
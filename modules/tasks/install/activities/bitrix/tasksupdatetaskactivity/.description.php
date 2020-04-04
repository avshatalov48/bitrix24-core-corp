<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('TASKS_UTA_NAME'),
	'DESCRIPTION' => GetMessage('TASKS_UTA_DESC'),
	'TYPE' => array('activity', 'robot_activity'),
	'CLASS' => 'TasksUpdateTaskActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => array(
		'ID' => 'document',
		"OWN_ID" => 'tasks',
		"OWN_NAME" => GetMessage('TASKS_UTA_CATEGORY'),
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
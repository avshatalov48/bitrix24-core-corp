<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('TASKS_DTA_NAME'),
	'DESCRIPTION' => GetMessage('TASKS_DTA_DESC'),
	'TYPE' => array('activity', 'robot_activity'),
	'CLASS' => 'TasksDeleteTaskActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => array(
		'ID' => 'document',
		"OWN_ID" => 'tasks',
		"OWN_NAME" => GetMessage('TASKS_DTA_CATEGORY'),
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
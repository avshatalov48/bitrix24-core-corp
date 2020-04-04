<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('TASKS_CHANGE_STATUS_NAME'),
	'DESCRIPTION' => GetMessage('TASKS_CHANGE_STATUS_DESC'),
	'TYPE' => array('activity', 'robot_activity'),
	'CLASS' => 'TasksChangeStatusActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => array(
		'ID' => 'document',
		"OWN_ID" => 'tasks',
		"OWN_NAME" => GetMessage('TASKS_CHANGE_STATUS_CATEGORY'),
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
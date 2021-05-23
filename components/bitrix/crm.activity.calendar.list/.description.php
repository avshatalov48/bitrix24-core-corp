<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('CRM_ACTIVITY_CALENDAR_LIST_NAME'),
	'DESCRIPTION' => GetMessage('CRM_ACTIVITY_CALENDAR_LIST_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 80,
	'PATH' => array(
		'ID' => 'crm',
		'NAME' => GetMessage('CRM_NAME'),
		'CHILD' => array(
			'ID' => 'activity',
			'NAME' => GetMessage('CRM_ACTIVITY_NAME')
		)
	),
	'CACHE_PATH' => 'Y'
);
?>
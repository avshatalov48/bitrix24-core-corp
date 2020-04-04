<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = [
	'NAME' => GetMessage('CRM_ORDER_BUYER_GROUP_EDIT_NAME'),
	'DESCRIPTION' => GetMessage('CRM_ORDER_BUYER_GROUP_EDIT_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 20,
	'PATH' => [
		'ID' => 'crm',
		'NAME' => GetMessage('CRM_NAME'),
		'CHILD' => [
			'ID' => 'orderform',
			'NAME' => GetMessage('CRM_ORDER_BUYER_GROUP_EDIT_NAME')
		]
	],
	'CACHE_PATH' => 'Y'
];
?>
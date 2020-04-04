<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('CRM_CONFIG_ORDER_FORM_LIST_NAME'),
	'DESCRIPTION' => GetMessage('CRM_CONFIG_ORDER_FORM_LIST_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 20,
	'PATH' => array(
		'ID' => 'crm',
		'NAME' => GetMessage('CRM_NAME'),
		'CHILD' => array(
			'ID' => 'order-matcher-property-edit',
			'NAME' => GetMessage('CRM_CONFIG_ORDER_FORM_NAME')
		)
	),
	'CACHE_PATH' => 'Y'
);
?>
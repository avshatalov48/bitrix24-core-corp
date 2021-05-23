<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('CRM_EXCH1C_NAME'),
	'DESCRIPTION' => GetMessage('CRM_EXCH1C_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 800,
	'CACHE_PATH' => 'Y',
	'PATH' => array(
		'ID' => 'crm',
		'NAME' => GetMessage('CRM_NAME'),
		'CHILD' => array(
			'ID' => 'config',
			'NAME' => GetMessage('CRM_CONFIG_NAME'),
		)
	),
);

?>
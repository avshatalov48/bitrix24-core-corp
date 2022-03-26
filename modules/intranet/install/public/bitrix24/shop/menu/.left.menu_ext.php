<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$result = $GLOBALS['APPLICATION']->includeComponent(
	'bitrix:crm.admin.page.controller',
	'',
	[
		'SEF_FOLDER' => '/shop/settings/',
		'MENU_MODE' => 'Y',
		'MENU_ID' => 'store',
	]
);

$aMenuLinks = is_array($result) && isset($result['ITEMS']) ? $result['ITEMS'] : [];
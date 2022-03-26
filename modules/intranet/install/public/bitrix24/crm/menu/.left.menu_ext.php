<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$result = $GLOBALS['APPLICATION']->includeComponent(
	'bitrix:crm.control_panel',
	'',
	[
		'MENU_MODE' => 'Y'
	]
);

$aMenuLinks = is_array($result) && isset($result['ITEMS']) ? $result['ITEMS'] : [];
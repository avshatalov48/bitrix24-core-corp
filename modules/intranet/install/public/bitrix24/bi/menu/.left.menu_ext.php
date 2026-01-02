<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$result = $GLOBALS['APPLICATION']->includeComponent(
	'bitrix:biconnector.apachesuperset.control_panel',
	'',
	[
		'MENU_MODE' => 'Y',
	]
);

if ($result instanceof \Bitrix\Main\Result && $result->isSuccess())
{
	$data = $result->getData();
	$aMenuLinks = is_array($data) && isset($data['MENU_ITEMS']) ? $data['MENU_ITEMS'] : [];
}

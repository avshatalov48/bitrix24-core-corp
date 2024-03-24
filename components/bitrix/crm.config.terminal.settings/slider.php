<?php

/**
 * @var CMain $APPLICATION
 */

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

\Bitrix\Main\Loader::includeModule('crm');

$APPLICATION->ShowHead();

$APPLICATION->IncludeComponent(
	'bitrix:crm.config.terminal.settings',
	'',
	[]
);

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
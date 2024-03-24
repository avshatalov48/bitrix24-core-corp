<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

Loc::loadMessages(__FILE__);

if(!Loader::includeModule('crm'))
{
	ShowError(Loc::getMessage('CRM_MODULE_NOT_INSTALLED'));
}

/** @global \CMain $APPLICATION */
global $APPLICATION;

$APPLICATION->IncludeComponent(
	'bitrix:crm.requisite.details.slider',
	'',
	[
		'IS_OPENED_IN_ENTITY_DETAILS' => true,
	],
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
exit;

<?php

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\UI\Toolbar\Facade\Toolbar;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var CMain $APPLICATION
 * @var array $arResult
 */

Container::getInstance()->getLocalization()->loadMessages();
$APPLICATION->SetTitle(Loc::getMessage('CRM_COMMON_PERMISSIONS_SETTINGS_ITEM'));

global $APPLICATION;
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.config.perms.v2',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => $arResult['componentParams'],
		'PAGE_MODE' => false,
		'PAGE_MODE_OFF_BACK_URL' => $arResult['backUrl'],
		'USE_UI_TOOLBAR' => 'N',
		'USE_PADDING' => false,
		'PLAIN_VIEW' => false,
		'HIDE_TOOLBAR' => true,
	],
);

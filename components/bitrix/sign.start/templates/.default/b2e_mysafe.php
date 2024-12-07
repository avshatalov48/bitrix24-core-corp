<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

/** @var SignStartComponent $component */
$component->setMenuIndex('sign_b2e_mysafe');

/** @var CMain $APPLICATION */
/** @var array $arParams */

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:sign.document.list',
		'POPUP_COMPONENT_PARAMS' => [
			'COMPONENT_TYPE' => 'safe',
		],
		'USE_UI_TOOLBAR' => 'Y',
	],
	$this->getComponent(),
);

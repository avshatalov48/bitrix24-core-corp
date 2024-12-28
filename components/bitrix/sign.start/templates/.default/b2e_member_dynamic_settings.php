<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var SignStartComponent $component */
$component->setMenuIndex('sign_b2e_member_dynamic_settings');

/** @var CMain $APPLICATION */
/** @var array $arParams */

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:sign.b2e.member.dynamic.settings',
		'POPUP_COMPONENT_PARAMS' => [],
	],
	$this->getComponent(),
);
<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var SignStartComponent $component */
$component->setMenuIndex('sign_b2e_my_documents');

/** @var CMain $APPLICATION */
/** @var array $arParams */

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:sign.my-documents.list',
		'POPUP_COMPONENT_PARAMS' => [
			'ENTITY_ID' => \Bitrix\Main\Engine\CurrentUser::get()->getId(),
		],
		'USE_UI_TOOLBAR' => 'Y',
	],
	$this->getComponent(),
);

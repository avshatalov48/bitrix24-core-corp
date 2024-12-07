<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arParams */
/** @var \CMain $APPLICATION */
/** @var SignStartComponent $component */

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:sign.b2e.preview',
		'POPUP_COMPONENT_PARAMS' => [
			'VAR_DOC_ID' => 'docId',
		],
		'PLAIN_VIEW' => false,
		'USE_BACKGROUND_CONTENT' => false,
		'USE_PADDING' => false,
		'USE_UI_TOOLBAR' => true,
		'BUTTONS' => ['close'],
	],
	$this->getComponent()
);

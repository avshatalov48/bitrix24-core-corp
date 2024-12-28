<?php

/** @var $APPLICATION \CMain */
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$APPLICATION->SetPageProperty('BodyClass', 'ui-page-slider-wrapper-hr');

$APPLICATION->IncludeComponent(
	"bitrix:ui.sidepanel.wrapper",
	"",
	[
		"POPUP_COMPONENT_NAME" => "bitrix:humanresources.start",
		"POPUP_COMPONENT_PARAMS" => [
			'SEF_MODE' => 'Y',
			'SEF_FOLDER' => '/hr/',
		],
		"USE_UI_TOOLBAR" => "N",
		"PLAIN_VIEW" => "Y",
		"USE_PADDING" => false,
		"PAGE_MODE" => false,
		"USE_BACKGROUND_CONTENT" => false,
		'PAGE_MODE_OFF_BACK_URL' => "/company/",
	],
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');

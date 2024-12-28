<?php

use Bitrix\BIConnector;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

global $APPLICATION;

if (
	!Loader::includeModule('biconnector')
	|| !BIConnector\Configuration\Feature::isExternalEntitiesEnabled()
)
{
	LocalRedirect('/');
}

$request = Application::getInstance()->getContext()->getRequest();

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:biconnector.dataset.import',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'sourceId' => (string)($request->get('sourceId') ?? 0),
			'datasetId' => (int)($request->get('datasetId') ?? 0),
		],

		'CLOSE_AFTER_SAVE' => false,
		'RELOAD_GRID_AFTER_SAVE' => true,
		'IS_TOOL_PANEL_ALWAYS_VISIBLE' => true,
		'ENABLE_MODE_TOGGLE' => false,

		'USE_BACKGROUND_CONTENT' => false,
		'USE_PADDING' => false,
		'USE_UI_TOOLBAR' => 'Y',
	]
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');

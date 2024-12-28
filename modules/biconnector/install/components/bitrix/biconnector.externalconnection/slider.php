<?php

use Bitrix\BIConnector;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

if (
	!Loader::includeModule('biconnector')
	|| !BIConnector\Configuration\Feature::isExternalEntitiesEnabled()
)
{
	LocalRedirect('/');
}

$request = Application::getInstance()->getContext()->getRequest();
$sourceId = (int)$request->get('sourceId');

global $APPLICATION;
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper', '', [
		'POPUP_COMPONENT_NAME' => 'bitrix:biconnector.externalconnection',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'SOURCE_ID' => $sourceId,
		],

		'CLOSE_AFTER_SAVE' => true,
		'RELOAD_GRID_AFTER_SAVE' => true,
		'IS_TOOL_PANEL_ALWAYS_VISIBLE' => true,
		'ENABLE_MODE_TOGGLE' => false,

		'USE_BACKGROUND_CONTENT' => true,
		'USE_PADDING' => false,
		'USE_UI_TOOLBAR' => 'Y',
	]
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');

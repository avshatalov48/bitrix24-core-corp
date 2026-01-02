<?php

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

use Bitrix\BIConnector\Configuration\Feature;
use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Loader;

/**
 * @var CMain $APPLICATION
 */
if (
	!Loader::includeModule('biconnector')
	|| !method_exists('\Bitrix\BIConnector\Configuration\Feature', 'isExternalEntitiesEnabled')
	|| !Feature::isExternalEntitiesEnabled()
)
{
	LocalRedirect('/');
}
elseif (
	Loader::includeModule('intranet')
	&& !ToolsManager::getInstance()->checkAvailabilityByToolId('crm_bi')
)
{
	LocalRedirect('/bi/dashboard/');
}
else
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:biconnector.apachesuperset.workspace_analytic.controller',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => [
				'SEF_MODE' => 'Y',
				'SEF_FOLDER' => '/',
				'SEF_URL_TEMPLATES' => [
					'dataset' => 'bi/dataset/',
					'source' => 'bi/source/',
					'statistics' => 'bi/statistics/',
					'unused_elements' => 'bi/unused_elements/',
				],
			],
			'USE_UI_TOOLBAR' => 'Y',
			'USE_PADDING' => true,
			'PLAIN_VIEW' => false,
			'PAGE_MODE' => false,
			'PAGE_MODE_OFF_BACK_URL' => '/bi/dashboard/',
			'POPUP_COMPONENT_USE_BITRIX24_THEME' => 'Y',
		]
	);
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");

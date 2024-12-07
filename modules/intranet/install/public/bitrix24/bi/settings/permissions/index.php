<?php

use Bitrix\Main\Loader;
use Bitrix\Bitrix24\Feature;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

/**
 * @var CMain $APPLICATION
 */

global $USER;

if (
	!Loader::includeModule('report')
	|| !Loader::includeModule('biconnector')
	|| !Loader::includeModule('crm')
)
{
	$event = new \Bitrix\Main\Analytics\AnalyticsEvent('open_editor', 'BI_Builder', 'roles');
	$event
		->setSection('BI_Builder')
		->setElement('menu')
		->setStatus('blocked')
		->send()
	;

	echo 'Analytics is not enabled.';
}
elseif (Loader::includeModule('bitrix24') && !Feature::isFeatureEnabled('bi_constructor_rights'))
{
	$event = new \Bitrix\Main\Analytics\AnalyticsEvent('open_editor', 'BI_Builder', 'roles');
	$event
		->setSection('BI_Builder')
		->setElement('menu')
		->setStatus('blocked')
		->send()
	;

	LocalRedirect('/');
}
else
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:biconnector.apachesuperset.config.permissions',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => [],
			'USE_UI_TOOLBAR' => 'Y',
			'USE_PADDING' => false,
			'PLAIN_VIEW' => false,
			'PAGE_MODE' => false,
			'PAGE_MODE_OFF_BACK_URL' => '/bi/dashboard/'
		]
	);
}

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');

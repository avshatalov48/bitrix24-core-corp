<?php

use Bitrix\Main\Loader;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

/**
 * @var CMain $APPLICATION
 */

if (
	!Loader::includeModule('report')
	|| !Loader::includeModule('biconnector')
	|| !Loader::includeModule('crm')
)
{
	echo 'Analytics is not enabled.';
}
else
{
	$APPLICATION->IncludeComponent(
		'bitrix:biconnector.apachesuperset.dashboard.controller',
		'',
		[
			'SEF_MODE' => 'Y',
			'SEF_FOLDER' => '/',
			'SEF_URL_TEMPLATES' => [
				'list' => 'bi/dashboard/',
				'detail' => 'bi/dashboard/detail/#dashboardId#/'
			],
		]
	);
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");

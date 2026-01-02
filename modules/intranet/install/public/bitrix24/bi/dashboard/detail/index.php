<?php

require($_SERVER["DOCUMENT_ROOT"].'/bitrix/header.php');
global $APPLICATION;


if (\Bitrix\Main\Loader::includeModule('biconnector'))
{
	$APPLICATION->IncludeComponent(
		'bitrix:biconnector.apachesuperset.dashboard.detail',
		"",
		[
			'DASHBOARD_ID' => $_REQUEST['dashboardId'] ?? '',
		]
	);
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');

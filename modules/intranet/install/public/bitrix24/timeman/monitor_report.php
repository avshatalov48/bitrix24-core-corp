<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Monitor\Config;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

if (
	Loader::includeModule('timeman')
	&& class_exists('\Bitrix\Timeman\Monitor\Config')
	&& method_exists('\Bitrix\Timeman\Monitor\Config', 'isAvailable')
	&& Config::isAvailable()
)
{
	Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intranet/public_bitrix24/timeman/monitor_report.php');

	/** @var CMain $APPLICATION */
	$APPLICATION->SetTitle(Loc::getMessage('TIMEMAN_MONITOR_REPORT_PAGE_TITLE'));
	$APPLICATION->IncludeComponent(
		'bitrix:timeman.monitor.report',
		'',
		[]
	);
}

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
<?php

define('SKIP_TEMPLATE_AUTH_ERROR', true);
define('NOT_CHECK_PERMISSIONS', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (\Bitrix\Main\Loader::includeModule('bitrix24'))
{
	$zone = \CBitrix24::getPortalZone();
}
else
{
	$iterator = Bitrix\Main\Localization\LanguageTable::getList([
		'select' => ['ID'],
		'filter' => [
			'=DEF' => 'Y',
			'=ACTIVE' => 'Y'
		]
	]);
	$row = $iterator->fetch();
	$zone = $row['ID'];
}

\Bitrix\Main\Localization\Loc::setCurrentLang($zone);

\Bitrix\Main\Localization\Loc::loadMessages($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/pub/payment_slip.php");

define('CUSTOM_HEADER_TITLE', \Bitrix\Main\Localization\Loc::getMessage('PAYMENT_SLIP_CUSTOM_TITLE'));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_after.php");

global $APPLICATION;

$APPLICATION->SetPageProperty("BodyClass", "flexible-mode--linear-blue--v2");
$APPLICATION->SetPageProperty("title", \Bitrix\Main\Localization\Loc::getMessage('PAYMENT_SLIP_CUSTOM_TITLE'));
$APPLICATION->AddHeadString('<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">');


if (\Bitrix\Main\Loader::includeModule('salescenter'))
{
	$APPLICATION->IncludeComponent(
		'bitrix:salescenter.pub.payment.slip',
		'',
		[
			'SIGNED_PAYMENT_ID' => $_REQUEST['signed_payment_id'] ?? '',
		],
	);
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');

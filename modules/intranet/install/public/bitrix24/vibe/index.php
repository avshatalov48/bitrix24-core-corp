<?php

use Bitrix\Main\Loader;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intranet/public_bitrix24/index.php');

/** @var \CMain $APPLICATION */
$APPLICATION->SetPageProperty('NOT_SHOW_NAV_CHAIN', 'Y');
$APPLICATION->SetPageProperty('title', htmlspecialcharsbx(COption::GetOptionString('main', 'site_name', 'Bitrix24')));
Loader::includeModule('intranet');

GetGlobalID();

$componentDateTimeFormat = CIntranetUtils::getCurrentDateTimeFormat();

// todo: how hide top menu?

// todo: remove after open Vibe for all
if (
	Loader::includeModule('landing')
	&& \Bitrix\Landing\Mainpage\Manager::isAvailable()
)
{
	$APPLICATION->IncludeComponent(
		'bitrix:landing.mainpage.pub',
		'',
		[
			'DRAFT_MODE' => 'Y',
		],
		null,
		[
			'HIDE_ICONS' => 'Y',
		]
	);
}
else
{
	(new Bitrix\Intranet\MainPage\Publisher)->withdraw();
	LocalRedirect('/');
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");

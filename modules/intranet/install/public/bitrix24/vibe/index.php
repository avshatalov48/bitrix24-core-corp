<?php

use Bitrix\Main\Loader;
use Bitrix\Intranet\MainPage;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intranet/public_bitrix24/index.php');

/** @var \CMain $APPLICATION */
$APPLICATION->SetPageProperty('NOT_SHOW_NAV_CHAIN', 'Y');
$APPLICATION->SetPageProperty('title', htmlspecialcharsbx(COption::GetOptionString('main', 'site_name', 'Bitrix24')));

// todo: how hide top menu?

if (
	Loader::includeModule('landing')
	&& (new MainPage\Access())->canView()
)
{
	$APPLICATION->IncludeComponent(
		'bitrix:landing.mainpage.pub',
		'',
		[],
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

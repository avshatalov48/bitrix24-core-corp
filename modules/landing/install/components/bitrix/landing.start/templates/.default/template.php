<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

$sef = [];

foreach ($arParams['SEF_URL_TEMPLATES'] as $code => $url)
{
	$sef[$code] = $arParams['SEF_FOLDER'] . $url;
}

\Bitrix\Landing\Update\Stepper::show();
?>

<?$result = $APPLICATION->IncludeComponent(
	'bitrix:landing.sites',
	'.default',
	array(
		'TYPE' => $arParams['TYPE'],
		'PAGE_URL_SITE' => $arParams['PAGE_URL_SITE_SHOW'],
		'PAGE_URL_SITE_EDIT' => $arParams['PAGE_URL_SITE_EDIT'],
		'PAGE_URL_LANDING_EDIT' => $arParams['PAGE_URL_LANDING_EDIT'],
		'SEF' => $sef,
		'AGREEMENT' => $arResult['AGREEMENT']
	),
	$component
);?>
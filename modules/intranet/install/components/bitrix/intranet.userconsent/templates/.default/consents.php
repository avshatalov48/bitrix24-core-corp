<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var CMain $APPLICATION*/
/** @var array $arResult*/
/** @var array $arParams*/

global $APPLICATION;
$componentParameters = array(
	'NAME_TEMPLATE' => $arResult['NAME_TEMPLATE'],
	'PATH_TO_USER_PROFILE' => $arResult['PATH_TO_USER_PROFILE'],
	'PATH_TO_LIST' => $arResult['PATH_TO_LIST'],
	'PATH_TO_ADD' => $arResult['PATH_TO_ADD'],
	'PATH_TO_EDIT' => $arResult['PATH_TO_EDIT'],
	'PATH_TO_CONSENT_LIST' => $arResult['PATH_TO_CONSENTS'],
	'CAN_EDIT' => $arResult['CAN_EDIT'],
	'RENDER_FILTER_INTO_VIEW' => 'pagetitle'
);
if ($_REQUEST['IFRAME'] == 'Y')
{
	$APPLICATION->IncludeComponent(
		"bitrix:intranet.pageslider.wrapper",
		"",
		array(
			'POPUP_COMPONENT_NAME' => "bitrix:main.userconsent.consent.list",
			"POPUP_COMPONENT_TEMPLATE_NAME" => "",
			"POPUP_COMPONENT_PARAMS" => $componentParameters,
		)
	);
}
else
{
	$APPLICATION->IncludeComponent(
		"bitrix:main.userconsent.consent.list",
		"",
		$componentParameters
	);

}
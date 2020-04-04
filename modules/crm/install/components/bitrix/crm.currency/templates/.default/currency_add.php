<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;

$iframe = (isset($_REQUEST['IFRAME']) && ($_REQUEST['IFRAME'] === 'Y')) ? true : false;

$componentParameters = array(
	'IFRAME' => $iframe,
	'PATH_TO_CURRENCY_LIST' => $arResult['PATH_TO_CURRENCY_LIST'],
	'PATH_TO_CURRENCY_ADD' => $arResult['PATH_TO_CURRENCY_ADD'],
	'FORM_MODE' => 'ADD'
);

if ($iframe)
{
	$APPLICATION->IncludeComponent(
		'bitrix:intranet.pageslider.wrapper',
		'',
		array(
			'POPUP_COMPONENT_NAME' => 'bitrix:crm.currency.classifier',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => $componentParameters
		)
	);
}
else
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.currency.classifier',
		'',
		$componentParameters
	);
}
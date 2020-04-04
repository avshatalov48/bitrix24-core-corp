<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;

$componentParameters = [
	'PATH_TO_ORDER_FORM' => $arResult['PATH_TO_ORDER_FORM'],
	'PATH_TO_ORDER_FORM_WITH_PT' => $arResult['PATH_TO_ORDER_FORM_WITH_PT'],
	'PATH_TO_ORDER_PROPERTY_EDIT' => $arResult['PATH_TO_ORDER_PROPERTY_EDIT'],
	'PERSON_TYPE_ID' => isset($arResult['VARIABLES']['person_type_id']) ? (string)$arResult['VARIABLES']['person_type_id'] : '',
];

if (isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] === 'Y')
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.order.matcher.pageslider.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:crm.order.matcher.edit',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => $componentParameters,
		]
	);
}
else
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.order.matcher.edit',
		'',
		$componentParameters
	);
}
?>
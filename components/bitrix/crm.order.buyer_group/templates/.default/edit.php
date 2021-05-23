<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;

$componentParameters = [
	'PATH_TO_BUYER_GROUP_LIST' => $arResult['PATH_TO_BUYER_GROUP_LIST'],
	'PATH_TO_BUYER_GROUP_EDIT' => $arResult['PATH_TO_BUYER_GROUP_EDIT'],
	'GROUP_ID' => isset($arResult['VARIABLES']['group_id']) ? (int)$arResult['VARIABLES']['group_id'] : 0,
];

if (isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] === 'Y')
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.order.matcher.pageslider.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:crm.order.buyer_group.edit',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => $componentParameters,
		]
	);
}
else
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.order.buyer_group.edit',
		'',
		$componentParameters
	);
}
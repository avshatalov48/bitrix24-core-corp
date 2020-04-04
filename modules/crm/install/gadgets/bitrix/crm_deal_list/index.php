<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule('crm'))
	return false;

$arGadgetParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath('PATH_TO_DEAL_SHOW', $arGadgetParams['PATH_TO_DEAL_SHOW'], '/crm/deal/show/#deal_id#/');

if (!is_array($arGadgetParams) || !array_key_exists('DEAL_COUNT', $arGadgetParams) || $arGadgetParams['DEAL_COUNT'] <= 0)
	$arGadgetParams['DEAL_COUNT'] = 3;

$arFilter = array();
$arSort = array();

if (!empty($arGadgetParams['SORT']) && !empty($arGadgetParams['SORT_BY']))
	$arSort = array($arGadgetParams['SORT'] => $arGadgetParams['SORT_BY']);

if (!empty($arGadgetParams['STAGE_ID']))
	$arFilter['STAGE_ID'] = $arGadgetParams['STAGE_ID'];

if (!empty($arGadgetParams['ONLY_MY']) && $arGadgetParams['ONLY_MY'] == 'Y')
	$arFilter['ASSIGNED_BY_ID'] = $GLOBALS['USER']->GetID();

global $APPLICATION;
$APPLICATION->IncludeComponent(
	'bitrix:crm.deal.list',
	'gadget',
	Array(
		'PATH_TO_DEAL_SHOW' => $arGadgetParams['PATH_TO_DEAL_SHOW'],
		'SET_TITLE' => 'N',
		'GADGET_ID' => str_replace('@', '_', $id),
		'DEAL_COUNT' => $arGadgetParams['DEAL_COUNT'],
		'INTERNAL_FILTER' => $arFilter,
		'INTERNAL_SORT' => $arSort
	),
	false,
	array('HIDE_ICONS' => 'Y')
);
?>
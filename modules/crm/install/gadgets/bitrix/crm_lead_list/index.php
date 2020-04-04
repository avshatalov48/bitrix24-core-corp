<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule('crm'))
	return false;

$arGadgetParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath('PATH_TO_LEAD_SHOW', $arGadgetParams['PATH_TO_LEAD_SHOW'], '/crm/lead/show/#lead_id#/');

if (!is_array($arGadgetParams) || !array_key_exists('LEAD_COUNT', $arGadgetParams) || $arGadgetParams['LEAD_COUNT'] <= 0)
	$arGadgetParams['LEAD_COUNT'] = 5;

$arFilter = array();
$arSort = array();

if (!empty($arGadgetParams['SORT']) && !empty($arGadgetParams['SORT_BY']))
	$arSort = array($arGadgetParams['SORT'] => $arGadgetParams['SORT_BY']);

if (!empty($arGadgetParams['STATUS_ID']))
	$arFilter['STATUS_ID'] = $arGadgetParams['STATUS_ID'];

if (!empty($arGadgetParams['ONLY_MY']) && $arGadgetParams['ONLY_MY'] == 'Y')
	$arFilter['ASSIGNED_BY_ID'] = $GLOBALS['USER']->GetID();

/*if ($bCrm)
{*/
	$APPLICATION->IncludeComponent(
		'bitrix:crm.lead.list',
		'gadget',
		Array(
			'PATH_TO_LEAD_SHOW' => $arGadgetParams['PATH_TO_LEAD_SHOW'],
			'SET_TITLE' => 'N',
			'GADGET_ID' => str_replace('@', '_', $id),
			'LEAD_COUNT' => $arGadgetParams['LEAD_COUNT'],
			'INTERNAL_FILTER' => $arFilter,
			'INTERNAL_SORT' => $arSort
		),
		false,
		array('HIDE_ICONS' => 'Y')
	);
/*}
else
	echo GetMessage('GD_CRM_FEATURE_INACTIVE');*/
?>
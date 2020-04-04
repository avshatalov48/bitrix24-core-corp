<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule('crm'))
	return false;

$arGadgetParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arGadgetParams['PATH_TO_COMPANY_SHOW'], '/crm/company/show/#company_id#/');

if (!is_array($arGadgetParams) || !array_key_exists('COMPANY_COUNT', $arGadgetParams) || $arGadgetParams['COMPANY_COUNT'] <= 0)
	$arGadgetParams['COMPANY_COUNT'] = 5;

$arFilter = array();
$arSort = array();

if (!empty($arGadgetParams['SORT']) && !empty($arGadgetParams['SORT_BY']))
	$arSort = array($arGadgetParams['SORT'] => $arGadgetParams['SORT_BY']);

if (!empty($arGadgetParams['TYPE_ID']))
	$arFilter['COMPANY_TYPE'] = $arGadgetParams['TYPE_ID'];

if (!empty($arGadgetParams['ONLY_MY']) && $arGadgetParams['ONLY_MY'] == 'Y')
	$arFilter['CREATED_BY_ID'] = $GLOBALS['USER']->GetID();

/*if ($bCrm)
{*/
	$APPLICATION->IncludeComponent(
		'bitrix:crm.company.list',
		'gadget',
		Array(
			'PATH_TO_COMPANY_SHOW' => $arGadgetParams['PATH_TO_COMPANY_SHOW'],
			'SET_TITLE' => 'N',
			'GADGET_ID' => str_replace('@', '_', $id),
			'COMPANY_COUNT' => $arGadgetParams['COMPANY_COUNT'],
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
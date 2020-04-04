<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule('crm'))
	return false;

$arGadgetParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arGadgetParams['PATH_TO_CONTACT_SHOW'], '/crm/contact/show/#contact_id#/');

if (!is_array($arGadgetParams) || !array_key_exists('CONTACT_COUNT', $arGadgetParams) || $arGadgetParams['CONTACT_COUNT'] <= 0)
	$arGadgetParams['CONTACT_COUNT'] = 5;

$arFilter = array();
$arSort = array();

if (!empty($arGadgetParams['SORT']) && !empty($arGadgetParams['SORT_BY']))
	$arSort = array($arGadgetParams['SORT'] => $arGadgetParams['SORT_BY']);

if (!empty($arGadgetParams['TYPE_ID']))
	$arFilter['TYPE_ID'] = $arGadgetParams['TYPE_ID'];

if (!empty($arGadgetParams['ONLY_MY']) && $arGadgetParams['ONLY_MY'] == 'Y')
	$arFilter['ASSIGNED_BY_ID'] = $GLOBALS['USER']->GetID();

/*if ($bCrm)
{*/
	$APPLICATION->IncludeComponent(
		'bitrix:crm.contact.list',
		'gadget',
		Array(
			'PATH_TO_CONTACT_SHOW' => $arGadgetParams['PATH_TO_CONTACT_SHOW'],
			'SET_TITLE' => 'N',
			'GADGET_ID' => str_replace('@', '_', $id),
			'CONTACT_COUNT' => $arGadgetParams['CONTACT_COUNT'],
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
<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule('crm'))
	return false;

$arGadgetParams['PATH_TO_TASK_SHOW'] = CrmCheckPath('PATH_TO_TASK_SHOW', $arGadgetParams['PATH_TO_TASK_SHOW'], COption::GetOptionString('tasks', 'paths_task_user_edit', ''));		
	
if (!is_array($arGadgetParams) || !array_key_exists('TASK_COUNT', $arGadgetParams) || $arGadgetParams['TASK_COUNT'] <= 0)
	$arGadgetParams['TASK_COUNT'] = 5;
	
$arFilter = array();	
$arSort = array();	

if (strlen($arGadgetParams['TASK_TYPE_LIST']) <= 0)
	$arGadgetParams['TASK_TYPE_LIST'] = '';

if (!empty($arGadgetParams['SORT']) && !empty($arGadgetParams['SORT_BY']))
	$arSort = array($arGadgetParams['SORT'] => $arGadgetParams['SORT_BY']);

if (!empty($arGadgetParams['ONLY_MY']) && $arGadgetParams['ONLY_MY'] == 'Y')
	$arFilter['RESPONSIBLE_ID'] = $GLOBALS['USER']->GetID();	

$arFilter['STATUS'] = array(-2, -1, 1, 2, 3, 6, 7);
$arFilter['ENTITY_TYPE'] = $arGadgetParams['TASK_TYPE_LIST'];	

/*if ($bCrm)
{*/
	$APPLICATION->IncludeComponent(
		'bitrix:crm.activity.task.list', 
		'gadget', 
		array(
			'ENTITY_TYPE' => $arGadgetParams['TASK_TYPE_LIST'],
			'ACTIVITY_TASK_COUNT' => $arGadgetParams['TASK_COUNT'],
			'GADGET_ID' => str_replace('@', '_', $id),
			'INTERNAL_FILTER' => $arFilter,
			'INTERNAL_SORT' => $arSort,		
			'ACTIVITY_ENTITY_LINK' => 'Y',
			'GADGET' => 'Y',
		), 
		false,
		array('HIDE_ICONS' => 'Y')							
	);
/*}
else
	echo GetMessage('GD_CRM_FEATURE_INACTIVE');*/
?>
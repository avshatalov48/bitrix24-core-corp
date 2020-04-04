<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule('crm'))
	return false;

$arGadgetParams['PATH_TO_FULL_VIEW'] = CrmCheckPath('PATH_TO_FULL_VIEW', $arGadgetParams['PATH_TO_FULL_VIEW'], COption::GetOptionString('crm', 'path_to_activity_list'));

if (!is_array($arGadgetParams) || !array_key_exists('ITEM_COUNT', $arGadgetParams) || $arGadgetParams['ITEM_COUNT'] <= 0)
	$arGadgetParams['ITEM_COUNT'] = 5;

global $APPLICATION;

$APPLICATION->IncludeComponent(
	'bitrix:crm.activity.list', 
	'gadget', 
	array(
		'PERMISSION_TYPE' => 'WRITE',
		'ENABLE_TOOLBAR' => false,
		'DISPLAY_REFERENCE' => true,
		'DISPLAY_CLIENT' => true,
		'AJAX_MODE' => 'N',
		'PREFIX' => 'GADGET_MY_ACTIVITIES',
		'ITEM_COUNT' => $arGadgetParams['ITEM_COUNT'],
		'PATH_TO_FULL_VIEW' => $arGadgetParams['PATH_TO_FULL_VIEW'],
		'DEFAULT_FILTER' => array(
			'LOGIC' => 'AND',
			'RESPONSIBLE_ID' => CCrmSecurityHelper::GetCurrentUserID(),
			'__INNER_FILTER_RECENT_CHANGED' => array(
				'LOGIC' => 'OR',
				'COMPLETED' => 'N',
				'>=LAST_UPDATED' => ConvertTimeStamp(AddToTimeStamp(array('HH' => -1), time() + CTimeZone::GetOffset()), 'FULL')
			)
		)
	),
	false,
	array('HIDE_ICONS' => 'Y')
);

?>
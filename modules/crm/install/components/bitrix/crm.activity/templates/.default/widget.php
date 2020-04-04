<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;

use Bitrix\Crm\Activity;

$APPLICATION->SetTitle(GetMessage('CRM_ACTIVITY_WGT_PAGE_TITLE'));
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	array(
		'ID' => 'ACTIVITY_WIDGET',
		'ACTIVE_ITEM_ID' => 'MY_ACTIVITY',
		'PATH_TO_COMPANY_LIST' => isset($arResult['PATH_TO_COMPANY_LIST']) ? $arResult['PATH_TO_COMPANY_LIST'] : '',
		'PATH_TO_COMPANY_EDIT' => isset($arResult['PATH_TO_COMPANY_EDIT']) ? $arResult['PATH_TO_COMPANY_EDIT'] : '',
		'PATH_TO_COMPANY_WIDGET' => isset($arResult['PATH_TO_COMPANY_WIDGET']) ? $arResult['PATH_TO_COMPANY_WIDGET'] : '',
		'PATH_TO_CONTACT_LIST' => isset($arResult['PATH_TO_CONTACT_LIST']) ? $arResult['PATH_TO_CONTACT_LIST'] : '',
		'PATH_TO_CONTACT_WIDGET' => isset($arResult['PATH_TO_CONTACT_WIDGET']) ? $arResult['PATH_TO_CONTACT_WIDGET'] : '',
		'PATH_TO_DEAL_WIDGET' => isset($arResult['PATH_TO_DEAL_WIDGET']) ? $arResult['PATH_TO_DEAL_WIDGET'] : '',
		'PATH_TO_DEAL_INDEX' => isset($arResult['PATH_TO_DEAL_INDEX']) ? $arResult['PATH_TO_DEAL_INDEX'] : '',
		'PATH_TO_DEAL_LIST' => isset($arResult['PATH_TO_DEAL_LIST']) ? $arResult['PATH_TO_DEAL_LIST'] : '',
		'PATH_TO_DEAL_EDIT' => isset($arResult['PATH_TO_DEAL_EDIT']) ? $arResult['PATH_TO_DEAL_EDIT'] : '',
		'PATH_TO_LEAD_LIST' => isset($arResult['PATH_TO_LEAD_LIST']) ? $arResult['PATH_TO_LEAD_LIST'] : '',
		'PATH_TO_LEAD_EDIT' => isset($arResult['PATH_TO_LEAD_EDIT']) ? $arResult['PATH_TO_LEAD_EDIT'] : '',
		'PATH_TO_QUOTE_LIST' => isset($arResult['PATH_TO_QUOTE_LIST']) ? $arResult['PATH_TO_QUOTE_LIST'] : '',
		'PATH_TO_QUOTE_EDIT' => isset($arResult['PATH_TO_QUOTE_EDIT']) ? $arResult['PATH_TO_QUOTE_EDIT'] : '',
		'PATH_TO_INVOICE_LIST' => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
		'PATH_TO_INVOICE_EDIT' => isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '',
		'PATH_TO_REPORT_LIST' => isset($arResult['PATH_TO_REPORT_LIST']) ? $arResult['PATH_TO_REPORT_LIST'] : '',
		'PATH_TO_DEAL_FUNNEL' => isset($arResult['PATH_TO_DEAL_FUNNEL']) ? $arResult['PATH_TO_DEAL_FUNNEL'] : '',
		'PATH_TO_EVENT_LIST' => isset($arResult['PATH_TO_EVENT_LIST']) ? $arResult['PATH_TO_EVENT_LIST'] : '',
		'PATH_TO_PRODUCT_LIST' => isset($arResult['PATH_TO_PRODUCT_LIST']) ? $arResult['PATH_TO_PRODUCT_LIST'] : ''
	),
	$component
);

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$isSupervisor = CCrmPerms::IsAdmin($currentUserID)
	|| Bitrix\Crm\Integration\IntranetManager::isSupervisor($currentUserID);

if($isSupervisor && isset($_REQUEST['super']))
{
	$isSupervisor = strtoupper($_REQUEST['super']) === 'Y';
}

$guid = 'activity_widget';
$options = CUserOptions::GetOption('crm.widget_panel', $guid, array());
$enableDemo = !isset($options['enableDemoMode']) || $options['enableDemoMode'] === 'Y';

$rowData = $enableDemo ?
	Activity\CommunicationWidgetPanel::getActivityDemoRowData($isSupervisor)
	: Activity\CommunicationWidgetPanel::getActivityRowData($isSupervisor);

?><div class="bx-crm-view"><?
	$APPLICATION->IncludeComponent(
		'bitrix:crm.widget_panel',
		'',
		array(
			'GUID' => $guid,
			'ENTITY_TYPE' => CCrmOwnerType::ActivityName,
			'LAYOUT' => 'L50R50',
			'NAVIGATION_CONTEXT_ID' => $arResult['NAVIGATION_CONTEXT_ID'],
			'PATH_TO_WIDGET' => isset($arResult['PATH_TO_ACTIVITY_WIDGET']) ? $arResult['PATH_TO_ACTIVITY_WIDGET'] : '',
			'PATH_TO_LIST' => isset($arResult['PATH_TO_ACTIVITY_LIST']) ? $arResult['PATH_TO_ACTIVITY_LIST'] : '',
			'PATH_TO_DEMO_DATA' => $_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.activity/templates/.default/widget',
			'IS_SUPERVISOR' => $isSupervisor,
			'ROWS' => $rowData,
			'NAVIGATION_COUNTER_ID' => CCrmUserCounter::CurrentActivies,
			'DEMO_TITLE' => GetMessage('CRM_ACTIVITY_WGT_DEMO_TITLE'),
			'DEMO_CONTENT' => GetMessage(
				'CRM_ACTIVITY_WGT_DEMO_CONTENT',
				array(
					'#URL#' => $arResult['PATH_TO_ACTIVITY_LIST'],
					'#CLASS_NAME#' => 'crm-widg-white-link'
				)
			)
		)
	);
	?></div>
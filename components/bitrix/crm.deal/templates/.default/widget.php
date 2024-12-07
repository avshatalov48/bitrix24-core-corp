<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
$APPLICATION->SetTitle(GetMessage('CRM_DEAL_WGT_PAGE_TITLE_SHORT'));
\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	array(
		'ID' => 'DEAL_WIDGET',
		'ACTIVE_ITEM_ID' => 'DEAL',
		'PATH_TO_COMPANY_LIST' => isset($arResult['PATH_TO_COMPANY_LIST']) ? $arResult['PATH_TO_COMPANY_LIST'] : '',
		'PATH_TO_COMPANY_EDIT' => isset($arResult['PATH_TO_COMPANY_EDIT']) ? $arResult['PATH_TO_COMPANY_EDIT'] : '',
		'PATH_TO_CONTACT_LIST' => isset($arResult['PATH_TO_CONTACT_LIST']) ? $arResult['PATH_TO_CONTACT_LIST'] : '',
		'PATH_TO_DEAL_WIDGET' => isset($arResult['PATH_TO_DEAL_WIDGET']) ? $arResult['PATH_TO_DEAL_WIDGET'] : '',
		'PATH_TO_DEAL_INDEX' => isset($arResult['PATH_TO_DEAL_INDEX']) ? $arResult['PATH_TO_DEAL_INDEX'] : '',
		'PATH_TO_DEAL_LIST' => isset($arResult['PATH_TO_DEAL_LIST']) ? $arResult['PATH_TO_DEAL_LIST'] : '',
		'PATH_TO_DEAL_EDIT' => isset($arResult['PATH_TO_DEAL_EDIT']) ? $arResult['PATH_TO_DEAL_EDIT'] : '',
		'PATH_TO_DEAL_CATEGORY' => isset($arResult['PATH_TO_DEAL_CATEGORY']) ? $arResult['PATH_TO_DEAL_CATEGORY'] : '',
		'PATH_TO_DEAL_WIDGETCATEGORY' => isset($arResult['PATH_TO_DEAL_WIDGETCATEGORY']) ? $arResult['PATH_TO_DEAL_WIDGETCATEGORY'] : '',
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
	$isSupervisor = mb_strtoupper($_REQUEST['super']) === 'Y';
}

$categoryID = isset($arResult['VARIABLES']['category_id']) ? (int)$arResult['VARIABLES']['category_id'] : -1;

$pathToList = $categoryID >= 0 && isset($arResult['PATH_TO_DEAL_CATEGORY'])
	? CComponentEngine::makePathFromTemplate(
		$arResult['PATH_TO_DEAL_CATEGORY'],
		array('category_id' => $categoryID))
	: (isset($arResult['PATH_TO_DEAL_LIST']) ? $arResult['PATH_TO_DEAL_LIST'] : '');

$pathToKanban = $categoryID >= 0 && isset($arResult['PATH_TO_DEAL_KANBANCATEGORY'])
	? CComponentEngine::makePathFromTemplate(
		$arResult['PATH_TO_DEAL_KANBANCATEGORY'],
		array('category_id' => $categoryID))
	: (isset($arResult['PATH_TO_DEAL_KANBAN']) ? $arResult['PATH_TO_DEAL_KANBAN'] : '');

$pathToCalendar = $categoryID >= 0 && isset($arResult['PATH_TO_DEAL_CALENDARCATEGORY'])
	? CComponentEngine::makePathFromTemplate(
		$arResult['PATH_TO_DEAL_CALENDARCATEGORY'],
		array('category_id' => $categoryID))
	: (isset($arResult['PATH_TO_DEAL_CALENDAR']) ? $arResult['PATH_TO_DEAL_CALENDAR'] : '');

$pathToWidget = $categoryID >= 0 && isset($arResult['PATH_TO_DEAL_WIDGETCATEGORY'])
	? CComponentEngine::makePathFromTemplate(
		$arResult['PATH_TO_DEAL_WIDGETCATEGORY'],
		array('category_id' => $categoryID))
	: (isset($arResult['PATH_TO_DEAL_WIDGET']) ? $arResult['PATH_TO_DEAL_WIDGET'] : '');

$contextData = array();
$filterExtras = array();
if($categoryID >= 0)
{
	$contextData = array('dealCategoryID' => $categoryID);
	$filterExtras = array('dealCategoryID' => '?');
}

use Bitrix\Crm\Widget\Layout\DealWidget; ?><script>
	BX.ready(
		function()
		{
			BX.CrmDealCategory.infos = <?=CUtil::PhpToJSObject(Bitrix\Crm\Category\DealCategory::getJavaScriptInfos())?>;
			BX.CrmDealWidgetFactory.messages =
			{
				notSelected: "<?=GetMessageJS('CRM_DEAL_WGT_NO_SELECTED')?>",
				current: "<?=GetMessageJS('CRM_DEAL_WGT_CURRENT')?>",
				categoryConfigParamCaption: "<?=GetMessageJS('CRM_DEAL_WGT_DEAL_CATEGORY')?>"
			};
			BX.CrmWidgetManager.getCurrent().registerFactory(
				BX.CrmEntityType.names.deal,
				BX.CrmDealWidgetFactory.create(BX.CrmEntityType.names.deal, {})
			);
		}
	);
</script><?
?><div class="bx-crm-view"><?
	$APPLICATION->IncludeComponent(
		'bitrix:crm.widget_panel',
		'',
		array(
			'GUID' => $categoryID >= 0 ? 'deal_category_widget' : 'deal_widget',
			'ENTITY_TYPE' => 'DEAL',
			'LAYOUT' => 'L50R50',
			'NAVIGATION_CONTEXT_ID' => $arResult['NAVIGATION_CONTEXT_ID'],
			'CONTEXT_DATA' => $contextData,
			'PATH_TO_LIST' => $pathToList,
			'PATH_TO_WIDGET' => $pathToWidget,
			'PATH_TO_KANBAN' => $pathToKanban,
			'PATH_TO_CALENDAR' => $pathToCalendar,
			'PATH_TO_DEMO_DATA' => $_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.deal/templates/.default/widget',
			'IS_SUPERVISOR' => $isSupervisor,
			'ROWS' => DealWidget::getDefaultRows([
					'isSupervisor' => $isSupervisor,
					'filterExtras' => $filterExtras,
			]),
			'NAVIGATION_COUNTER_ID' => CCrmUserCounter::CurrentDealActivies,
			'DEMO_TITLE' => GetMessage('CRM_DEAL_WGT_DEMO_TITLE'),
			'DEMO_CONTENT' => GetMessage(
				'CRM_DEAL_WGT_DEMO_CONTENT',
				array(
					'#URL#' => CCrmOwnerType::GetEditUrl(CCrmOwnerType::Deal, 0, false),
					'#CLASS_NAME#' => 'crm-widg-white-link'
				)
			)
		)
	);
?></div>

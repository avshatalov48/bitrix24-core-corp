<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;
if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$currentUserPermissions = CCrmPerms::GetCurrentUserPermissions();
if (!CCrmAuthorizationHelper::CheckConfigurationUpdatePermission($currentUserPermissions))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

use Bitrix\Crm\Statistics;

$listID = isset($arParams['LIST_ID']) ? $arParams['LIST_ID'] : '';
if($listID === '')
{
	$listID = 'widget_slot_list';
}
$arResult['LIST_ID'] = $listID;

$arResult['COLUMNS'] = array(
	'BINDING' => array(
		'NAME' => 'BINDING',
		'TITLE' => GetMessage('CRM_WGT_SLT_LIST_COL_BINDING')
	),
	'OPTIONS' => array(
		'NAME' => 'OPTIONS',
		'TITLE' => ''
	),
	'CONTROL' => array(
		'NAME' => 'CONTROL',
		'TITLE' => GetMessage('CRM_WGT_SLT_LIST_COL_CONTROL')
	)
);

$arResult['LIMIT'] = array(
	'OVERALL' => Statistics\StatisticEntryManager::getOverallSlotLimit(),
	'ENTITY' => Statistics\StatisticEntryManager::getSlotLimit()
);
$arResult['TOTAL'] = 0;
$arResult['ITEMS'] = array();
$typeNames = array(
	Statistics\DealSumStatisticEntry::TYPE_NAME,
	Statistics\LeadSumStatisticEntry::TYPE_NAME,
	Statistics\InvoiceSumStatisticEntry::TYPE_NAME
);
foreach($typeNames as $typeName)
{
	$arResult['ITEMS'][] = Statistics\StatisticEntryManager::prepareSlotBingingData($typeName);
	$arResult['TOTAL'] += Statistics\StatisticEntryManager::getBusySlotCount($typeName);
}
$arResult['ENABLE_CONTROL_PANEL'] = isset($arParams['ENABLE_CONTROL_PANEL']) ? $arParams['ENABLE_CONTROL_PANEL'] : true;
$arResult['ENABLE_B24_HELPER'] = Bitrix\Crm\Integration\Bitrix24Manager::isEnabled();
$arResult['HELP_ARTICLE_URL'] = 'redirect=detail&HD_SOURCE=article&HD_ID='.GetMessage('CRM_WGT_SLT_LIST_HELP_ARTICLE_ID');
$this->IncludeComponentTemplate();
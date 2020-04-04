<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

use Bitrix\Crm\Activity\CustomType;

global $USER, $APPLICATION;
$curPageUrl = $APPLICATION->GetCurPage();
$arParams['PATH_TO_ACTIVITY_CUSTOM_TYPE_LIST'] = CrmCheckPath('PATH_TO_ACTIVITY_CUSTOM_TYPE_LIST', $arParams['PATH_TO_DEAL_CATEGORY_LIST'], $curPageUrl);
$arParams['TYPE'] = 'list';

$arResult['BUTTONS'] = array();

$userPermissions = CCrmPerms::GetCurrentUserPermissions();
if (!\CCrmAuthorizationHelper::CheckConfigurationUpdatePermission($userPermissions))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

//$restriction = RestrictionManager::getActivityCustomTypeLimitRestriction();
//$limit = $restriction->getQuantityLimit();
$limit = 0;
if($limit <= 0 || $limit > CustomType::getCount())
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_ACT_CUST_TYPE_ADD'),
		'TITLE' => GetMessage('CRM_ACT_CUST_TYPE_ADD_TITLE'),
		'ONCLICK' => 'BX.onCustomEvent(window, \'CrmActitityCustomTypeCreate\', [this]); return BX.PreventDefault(this);',
		'HIGHLIGHT' => true
	);
}
/*
else
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_ACT_CUST_TYPE_ADD'),
		'TITLE' => GetMessage('CRM_ACT_CUST_TYPE_ADD_TITLE'),
		'ONCLICK' => $restriction->preparePopupScript(),
		'HIGHLIGHT' => true
	);
}
*/
$this->IncludeComponentTemplate();
?>
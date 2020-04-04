<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Restriction\RestrictionManager;

global $USER, $APPLICATION;
$curPageUrl = $APPLICATION->GetCurPage();
$arParams['PATH_TO_DEAL_CATEGORY_LIST'] = CrmCheckPath('PATH_TO_DEAL_CATEGORY_LIST', $arParams['PATH_TO_DEAL_CATEGORY_LIST'], $curPageUrl);
$arParams['TYPE'] = 'list';

$arResult['BUTTONS'] = array();

$userPermissions = CCrmPerms::GetCurrentUserPermissions();
if (!$userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$restriction = RestrictionManager::getDealCategoryLimitRestriction();
$limit = $restriction->getQuantityLimit();
if($limit <= 0 || $limit > DealCategory::getCount())
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_DEAL_CATEGORY_ADD'),
		'TITLE' => GetMessage('CRM_DEAL_CATEGORY_ADD_TITLE'),
		'ONCLICK' => 'BX.onCustomEvent(window, \'CrmDealCategoryCreate\', [this]); return BX.PreventDefault(this);',
		'HIGHLIGHT' => true
	);
}
else
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_DEAL_CATEGORY_ADD'),
		'TITLE' => GetMessage('CRM_DEAL_CATEGORY_ADD_TITLE'),
		'ONCLICK' => $restriction->preparePopupScript(),
		'HIGHLIGHT' => true
	);
}

$this->IncludeComponentTemplate();
?>
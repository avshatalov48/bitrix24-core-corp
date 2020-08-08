<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $this
 * @global CMain $APPLICATION
 * @global CUser $USER
 */

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$userID =  CCrmPerms::GetCurrentUserID();
$userPermissions = CCrmPerms::GetCurrentUserPermissions();
if (!CCrmDeal::CheckReadPermission(0, $userPermissions))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Counter\EntityCounterFactory;
use Bitrix\Crm\Counter\EntityCounterType;

$arParams['PATH_TO_DEAL_LIST'] = CrmCheckPath('PATH_TO_DEAL_LIST', $arParams['PATH_TO_DEAL_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
$arParams['PATH_TO_DEAL_CATEGORY'] = CrmCheckPath('PATH_TO_DEAL_CATEGORY', $arParams['PATH_TO_DEAL_CATEGORY'], $APPLICATION->GetCurPage().'?category_id=#category_id#');
$arParams['ENABLE_CATEGORY_ALL'] = isset($arParams['ENABLE_CATEGORY_ALL']) ? $arParams['ENABLE_CATEGORY_ALL'] : 'Y';
$arResult['PATH_TO_DEAL_CATEGORY_LIST'] = CrmCheckPath('PATH_TO_DEAL_CATEGORY_LIST', $arParams['PATH_TO_DEAL_CATEGORY_LIST'], COption::GetOptionString('crm', 'path_to_deal_category_list'));
$arResult['PATH_TO_DEAL_CATEGORY_EDIT'] = CrmCheckPath('PATH_TO_DEAL_CATEGORY_EDIT', $arParams['PATH_TO_DEAL_CATEGORY_EDIT'], COption::GetOptionString('crm', 'path_to_deal_category_edit'));

$arResult['CATEGORY_ID'] = $arParams['CATEGORY_ID'] = isset($arParams['CATEGORY_ID']) ? (int)$arParams['CATEGORY_ID'] : -1;
$arResult['CATEGORY_NAME'] = '';
$arResult['CATEGORY_COUNTER_ID'] = '';
$arResult['CATEGORY_COUNTER_VALUE'] = 0;
$arResult['GUID'] = isset($arParams['GUID']) ? $arParams['GUID'] : 'deal_category_panel';
$arResult['ITEMS'] = array();

$arResult['IS_CUSTOMIZED'] = false;
$arResult['DEFAULT_INDEX'] = -1;
$arResult['ACTIVE_INDEX'] = -1;

$totalCounter = EntityCounterFactory::create(
	CCrmOwnerType::Deal,
	EntityCounterType::ALL,
	$userID
);
$arResult['CATEGORY_COUNTER_CODE'] = $totalCounter->getCode();
$arResult['CATEGORY_COUNTER'] = $totalCounter->getValue();

$map = array_fill_keys(CCrmDeal::GetPermittedToReadCategoryIDs($userPermissions), true);
foreach(DealCategory::getAll(true) as $item)
{
	$ID = (int)$item['ID'];
	if(!isset($map[$ID]))
	{
		continue;
	}

	$counter = EntityCounterFactory::create(
		CCrmOwnerType::Deal,
		EntityCounterType::ALL,
		$userID,
		array('DEAL_CATEGORY_ID' => $ID)
	);

	$isActive = $ID === $arResult['CATEGORY_ID'];
	if($isActive)
	{
		$arResult['CATEGORY_NAME'] = isset($item['NAME']) ? $item['NAME'] : "[{$ID}]";
	}

	$arResult['ITEMS'][] = array(
		'ID' => $ID,
		'NAME' => isset($item['NAME']) ? $item['NAME'] : "[{$ID}]",
		'IS_ACTIVE' => $isActive,
		'COUNTER_CODE' => $counter->getCode(),
		'COUNTER' => $counter->getValue(),
		'URL' => CComponentEngine::makePathFromTemplate($arParams['PATH_TO_DEAL_CATEGORY'], array('category_id' => $ID)),
		'CREATE_URL' => CCrmUrlUtil::AddUrlParams(
			CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_EDIT'], array('deal_id' => 0)),
			array('category_id' => $ID)
		)
	);

	$index = count($arResult['ITEMS']) - 1;
	if($isActive)
	{
		$arResult['ACTIVE_INDEX'] = $index;
	}

	$isDefault = isset($item['IS_DEFAULT']) && $item['IS_DEFAULT'] === true;
	if($isDefault)
	{
		$arResult['DEFAULT_INDEX'] = $index;
	}
	elseif(!$arResult['IS_CUSTOMIZED'])
	{
		$arResult['IS_CUSTOMIZED'] = true;
	}
}

if(!$arResult['IS_CUSTOMIZED'])
{
	$arResult['ITEMS'][$arResult['DEFAULT_INDEX']]['ENABLED'] = false;
	if($arResult['DEFAULT_INDEX'] === $arResult['ACTIVE_INDEX'])
	{
		$arResult['ITEMS'][$arResult['DEFAULT_INDEX']]['IS_ACTIVE'] = false;
		$arResult['ACTIVE_INDEX'] = -1;
	}
}
elseif($arParams['ENABLE_CATEGORY_ALL'] === 'Y')
{
	$arResult['ITEMS'] = array_merge(
		array(
			array(
				'NAME' => GetMessage('CRM_DEAL_CATEGORY_PANEL_ALL'),
				'IS_ACTIVE' => $arResult['CATEGORY_ID'] < 0,
				'URL' => $arParams['PATH_TO_DEAL_LIST']
			)
		),
		$arResult['ITEMS']
	);
}

if($arResult['ACTIVE_INDEX'] < 0)
{
	$arResult['ACTIVE_INDEX'] = 0;
	$arResult['ITEMS'][0]['IS_ACTIVE'] = true;
}

$arResult['CAN_CREATE_CATEGORY'] = $userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
if($arResult['CAN_CREATE_CATEGORY'])
{
	$restriction = RestrictionManager::getDealCategoryLimitRestriction();
	$limit = $restriction->getQuantityLimit();
	if($limit <= 0 || ($limit > DealCategory::getCount()))
	{
		$arResult['CATEGORY_CREATE_URL'] = CComponentEngine::MakePathFromTemplate(
			$arResult['PATH_TO_DEAL_CATEGORY_EDIT'],
			array('category_id' => 0)
		);
	}
	else
	{
		$arResult['CATEGORY_CREATE_URL'] = '';
		$arResult['CREATE_CATEGORY_LOCK_SCRIPT'] = $restriction->prepareInfoHelperScript();
	}
}

if($arResult['CAN_CREATE_CATEGORY'] || $arResult['IS_CUSTOMIZED'])
{
	$this->IncludeComponentTemplate();
}

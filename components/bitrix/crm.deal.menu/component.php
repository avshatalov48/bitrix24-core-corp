<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arParams
 * @var array $arResult
 * @var \CBitrixComponent $component
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 * @global CDatabase $DB
 */

if (!CModule::IncludeModule('crm'))
{
	return;
}

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Component\EntityList\Settings\PermissionItem;
use Bitrix\Crm\Integration\Sender\Rc;
use Bitrix\Crm\Recurring;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

Container::getInstance()->getLocalization()->loadMessages();

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$CrmPerms = CCrmPerms::GetCurrentUserPermissions();
if (!CCrmDeal::CheckReadPermission(0, $CrmPerms))
{
	return;
}

$arParams['PATH_TO_DEAL_LIST'] = CrmCheckPath(
	'PATH_TO_DEAL_LIST',
	$arParams['PATH_TO_DEAL_LIST'] ?? '',
	$APPLICATION->GetCurPage()
);
$arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath(
	'PATH_TO_DEAL_SHOW',
	$arParams['PATH_TO_DEAL_SHOW'] ?? '',
	$APPLICATION->GetCurPage() . '?deal_id=#deal_id#&show'
);
$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath(
	'PATH_TO_DEAL_EDIT',
	$arParams['PATH_TO_DEAL_EDIT'] ?? '',
	$APPLICATION->GetCurPage() . '?deal_id=#deal_id#&edit'
);
$arParams['PATH_TO_DEAL_DETAILS'] = CrmCheckPath(
	'PATH_TO_DEAL_DETAILS',
	$arParams['PATH_TO_DEAL_DETAILS'] ?? '',
	$APPLICATION->GetCurPage() . '?deal_id=#deal_id#&details'
);
$arParams['PATH_TO_DEAL_IMPORT'] = CrmCheckPath(
	'PATH_TO_DEAL_IMPORT',
	$arParams['PATH_TO_DEAL_IMPORT'] ?? '',
	$APPLICATION->GetCurPage() . '?import'
);
$arParams['PATH_TO_DEAL_RECUR'] = CrmCheckPath(
	'PATH_TO_DEAL_RECUR',
	$arParams['PATH_TO_DEAL_RECUR'] ?? '',
	$APPLICATION->GetCurPage() . "?recur"
);
$arParams['PATH_TO_DEAL_RECUR_SHOW'] = CrmCheckPath(
	'PATH_TO_DEAL_RECUR_SHOW',
	$arParams['PATH_TO_DEAL_RECUR_SHOW'] ?? '',
	$arParams['PATH_TO_DEAL_RECUR'] . '?deal_id=#deal_id#&show'
);
$arParams['PATH_TO_DEAL_RECUR_EDIT'] = CrmCheckPath(
	'PATH_TO_DEAL_RECUR_EDIT',
	$arParams['PATH_TO_DEAL_RECUR_EDIT'] ?? '',
	$arParams['PATH_TO_DEAL_RECUR'] . '?deal_id=#deal_id#&edit'
);
$arParams['PATH_TO_DEAL_RECUR_EXPOSE'] = CrmCheckPath(
	'PATH_TO_DEAL_RECUR_EXPOSE',
	$arParams['PATH_TO_DEAL_RECUR_EXPOSE'] ?? '',
	$APPLICATION->GetCurPage() . '?deal_id=#deal_id#&edit&recur&expose=Y'
);
$arResult['PATH_TO_DEAL_CATEGORY_LIST'] = CrmCheckPath(
	'PATH_TO_DEAL_CATEGORY_LIST',
	$arParams['PATH_TO_DEAL_CATEGORY_LIST'] ?? '',
	COption::GetOptionString('crm', 'path_to_deal_category_list')
);
$arResult['PATH_TO_DEAL_CATEGORY_EDIT'] = CrmCheckPath(
	'PATH_TO_DEAL_CATEGORY_EDIT',
	$arParams['PATH_TO_DEAL_CATEGORY_EDIT'] ?? '',
	COption::GetOptionString('crm', 'path_to_deal_category_edit')
);

$arParams['PATH_TO_MIGRATION'] = Loader::includeModule('market')
	? \Bitrix\Crm\Integration\Market\Router::getBasePath() . 'collection/migration_crm/'
	: \Bitrix\Crm\Integration\Market\Router::getCategoryPath('migration');
$arParams['PATH_TO_DEAL_WIDGET'] = CrmCheckPath(
	'PATH_TO_DEAL_WIDGET',
	$arParams['PATH_TO_DEAL_WIDGET'] ?? '',
	$APPLICATION->GetCurPage() . "?widget"
);
$arParams['PATH_TO_DEAL_KANBAN'] = CrmCheckPath(
	'PATH_TO_DEAL_KANBAN',
	$arParams['PATH_TO_DEAL_KANBAN'] ?? '',
	$APPLICATION->GetCurPage() . "?kanban"
);
$arParams['PATH_TO_DEAL_CALENDAR'] = CrmCheckPath(
	'PATH_TO_DEAL_CALENDAR',
	$arParams['PATH_TO_DEAL_CALENDAR'] ?? '',
	$APPLICATION->GetCurPage() . "?calendar"
);
$arParams['PATH_TO_DEAL_CATEGORY'] = CrmCheckPath(
	'PATH_TO_DEAL_CATEGORY',
	$arParams['PATH_TO_DEAL_CATEGORY'] ?? '',
	$APPLICATION->GetCurPage() . "?category=#category_id#"
);
$arParams['PATH_TO_DEAL_RECUR_CATEGORY'] = CrmCheckPath(
	'PATH_TO_DEAL_RECUR_CATEGORY',
	$arParams['PATH_TO_DEAL_RECUR_CATEGORY'] ?? '',
	$APPLICATION->GetCurPage() . '?category_id=#category_id#'
);
$arParams['PATH_TO_DEAL_WIDGETCATEGORY'] = CrmCheckPath(
	'PATH_TO_DEAL_WIDGETCATEGORY',
	$arParams['PATH_TO_DEAL_WIDGETCATEGORY'] ?? '',
	$APPLICATION->GetCurPage() . '?category_id=#category_id#'
);
$arParams['PATH_TO_DEAL_KANBANCATEGORY'] = CrmCheckPath(
	'PATH_TO_DEAL_KANBANCATEGORY',
	$arParams['PATH_TO_DEAL_KANBANCATEGORY'] ?? '',
	$APPLICATION->GetCurPage() . '?category_id=#category_id#'
);
$arParams['PATH_TO_DEAL_CALENDARCATEGORY'] = CrmCheckPath(
	'PATH_TO_DEAL_CALENDARCATEGORY',
	$arParams['PATH_TO_DEAL_CALENDARCATEGORY'] ?? '',
	$APPLICATION->GetCurPage() . '?category_id=#category_id#'
);

$arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? (int)$arParams['ELEMENT_ID'] : 0;

if($arParams['ELEMENT_ID'] > 0)
{
	$arResult['CATEGORY_ID'] = CCrmDeal::GetCategoryID($arParams['ELEMENT_ID']);
}
else
{
	$arResult['CATEGORY_ID'] = isset($arParams['CATEGORY_ID']) ? (int)$arParams['CATEGORY_ID'] : -1;
}

$arResult['CONVERSION_PERMITTED'] = isset($arParams['CONVERSION_PERMITTED']) ? $arParams['CONVERSION_PERMITTED'] : true;

if (!isset($arParams['TYPE']))
{
	$arParams['TYPE'] = 'list';
}

if (isset($_REQUEST['copy']))
{
	$arParams['TYPE'] = 'copy';
}

$toolbarID = 'toolbar_deal_'.$arParams['TYPE'];
if ($arParams['ELEMENT_ID'] > 0)
{
	$toolbarID .= '_'.$arParams['ELEMENT_ID'];
}

$arResult['TOOLBAR_ID'] = $toolbarID;

$arResult['BUTTONS'] = [];

$isInSlider = isset($arParams['IN_SLIDER']) && $arParams['IN_SLIDER'] === 'Y';
$currentCategoryID = $arResult['CATEGORY_ID'] ?? -1;
$bConfig = false;
if ($arParams['TYPE'] === 'list')
{
	$bRead = CCrmDeal::CheckReadPermission(0, $CrmPerms, $currentCategoryID);
	$bExport = CCrmDeal::CheckExportPermission($CrmPerms, $currentCategoryID);
	$bImport = CCrmDeal::CheckImportPermission($CrmPerms, $currentCategoryID) && ($arParams['IS_RECURRING'] ?? null) !== 'Y';
	$bAdd = CCrmDeal::CheckCreatePermission($CrmPerms, $currentCategoryID);
	$bWrite = CCrmDeal::CheckUpdatePermission(0, $CrmPerms, $currentCategoryID);

	$bDelete = false;
	$bConfig = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
}
else
{
	$bExport = false;
	$bImport = false;
	$bRead = CCrmDeal::CheckReadPermission($arParams['ELEMENT_ID'], $CrmPerms, $currentCategoryID);
	$bAdd = CCrmDeal::CheckCreatePermission($CrmPerms, $currentCategoryID);
	$bWrite = CCrmDeal::CheckUpdatePermission($arParams['ELEMENT_ID'], $CrmPerms, $currentCategoryID);
	$bDelete = CCrmDeal::CheckDeletePermission($arParams['ELEMENT_ID'], $CrmPerms, $currentCategoryID);

}

$bExclude = \Bitrix\Crm\Exclusion\Access::current()->canWrite();

if (isset($arParams['DISABLE_IMPORT']) && $arParams['DISABLE_IMPORT'] == 'Y')
{
	$bImport = false;
}

if (isset($arParams['DISABLE_DEDUPE']) && $arParams['DISABLE_DEDUPE'] == 'Y')
{
	$bDedupe = false;
}

if (isset($arParams['DISABLE_EXPORT']) && $arParams['DISABLE_EXPORT'] == 'Y')
{
	$bExport = false;
}

$isSliderEnabled = \CCrmOwnerType::IsSliderEnabled(\CCrmOwnerType::Deal);

if (!$bRead && !$bAdd && !$bWrite)
{
	return false;
}

//Skip COPY menu in slider mode
if ($arParams['TYPE'] === 'copy' && $isSliderEnabled)
{
	return false;
}

if ($arParams['TYPE'] === 'details')
{
	if ($arParams['ELEMENT_ID'] <= 0)
	{
		return false;
	}

	$scripts = isset($arParams['~SCRIPTS']) && is_array($arParams['~SCRIPTS']) ? $arParams['~SCRIPTS'] : [];

	//region APPLICATION PLACEMENT
	$placementGroupInfos = \Bitrix\Crm\Integration\Rest\AppPlacementManager::getHandlerInfos(
		\Bitrix\Crm\Integration\Rest\AppPlacement::DEAL_DETAIL_TOOLBAR
	);

	foreach ($placementGroupInfos as $placementGroupName => $placementInfos)
	{
		$arResult['BUTTONS'][] = array(
			'TYPE' => 'rest-app-toolbar',
			'NAME' => $placementGroupName,
			'DATA' => array(
				'OWNER_INFO' => $arParams['OWNER_INFO'] ?? [],
				'PLACEMENT' => \Bitrix\Crm\Integration\Rest\AppPlacement::DEAL_DETAIL_TOOLBAR,
				'APP_INFOS' => $placementInfos
			)
		);
	}
	//endregion

	if (!empty($arParams['BIZPROC_STARTER_DATA']))
	{
		$arResult['BUTTONS'][] = array(
			'TYPE' => 'bizproc-starter-button',
			'DATA' => $arParams['BIZPROC_STARTER_DATA']
		);
	}

	if($arParams['IS_RECURRING'] !== 'Y')
	{
		CCrmDeal::PrepareConversionPermissionFlags($arParams['ELEMENT_ID'], $arResult, $CrmPerms);
		if($arResult['CAN_CONVERT'])
		{
			$schemeID = \Bitrix\Crm\Conversion\DealConversionConfig::getCurrentSchemeID();
			$arResult['BUTTONS'][] = array(
				'TYPE' => 'toolbar-conv-scheme',
				'PARAMS' => array(
					'NAME' => 'deal_converter',
					'CONTAINER_ID' => $arParams['CONVERSION_CONTAINER_ID'],
					'LABEL_ID' => $arParams['CONVERSION_LABEL_ID'],
					'BUTTON_ID' => $arParams['CONVERSION_BUTTON_ID'],
					'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
					'ENTITY_TYPE_NAME' => CCrmOwnerType::DealName,
					'ENTITY_ID' => $arParams['ELEMENT_ID'],
					'SCHEME_ID' => $schemeID,
					'SCHEME_NAME' => \Bitrix\Crm\Conversion\DealConversionScheme::resolveName($schemeID),
					'SCHEME_DESCRIPTION' => \Bitrix\Crm\Conversion\DealConversionScheme::getDescription($schemeID),
					'IS_PERMITTED' => $arResult['CONVERSION_PERMITTED'],
					'LOCK_SCRIPT' => isset($arResult['CONVERSION_LOCK_SCRIPT']) ? $arResult['CONVERSION_LOCK_SCRIPT'] : '',
				),
				'CODE' => 'convert',
				'TEXT' => GetMessage('DEAL_CREATE_ON_BASIS'),
				'TITLE' => GetMessage('DEAL_CREATE_ON_BASIS_TITLE_MSGVER_1'),
				'ICON' => 'btn-convert'
			);
		}
	}
	elseif ($bAdd)
	{
		$exposeUrl = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_DEAL_DETAILS'],
				array('deal_id' => $arParams['ELEMENT_ID'])
			),
			array('expose' => 1)
		);

		$exposeData = array(
			'entityId' => $arParams['ELEMENT_ID'],
			'entityTypeId' => CCrmOwnerType::DealRecurring
		);

		$dealRecurringRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getDealRecurringRestriction();
		if ($dealRecurringRestriction->hasPermission())
		{
			$scriptRecurring = "
				BX.Crm.Page.open('".CUtil::JSEscape($exposeUrl)."');
				BX.onCustomEvent(window, 'CrmDealRecurringExpose', [ this, ".CUtil::PhpToJSObject($exposeData)." ]);
			";
			$icon = 'btn-copy';
		}
		else
		{
			$scriptRecurring = $dealRecurringRestriction->prepareInfoHelperScript();
			$icon = 'grid-lock';
		}
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('DEAL_DETAIL_EXPOSE'),
			'TITLE' => GetMessage('DEAL_DETAIL_EXPOSE_TITLE'),
			'ONCLICK' => $scriptRecurring,
			'ICON' => $icon
		);
	}

	//Force start new bar after first button
	$arResult['BUTTONS'][] = array('NEWBAR' => true);

	if($bWrite)
	{
		$arResult['BUTTONS'][] = array(
			'TYPE' => 'crm-communication-panel',
			'DATA' => array(
				'ENABLE_CALL' => \Bitrix\Main\ModuleManager::isModuleInstalled('calendar'),
				'OWNER_INFO' => isset($arParams['OWNER_INFO']) ? $arParams['OWNER_INFO'] : [],
				'MULTIFIELDS' => isset($arParams['MULTIFIELD_DATA']) ? $arParams['MULTIFIELD_DATA'] : []
			)
		);
	}

	if($bAdd)
	{
		$copyUrl = \Bitrix\Crm\Integration\Analytics\Builder\Entity\CopyOpenEvent::createDefault(\CCrmOwnerType::Deal)
			->setSection(
				!empty($arParams['ANALYTICS']['c_section']) && is_string($arParams['ANALYTICS']['c_section'])
					? $arParams['ANALYTICS']['c_section']
					: null
			)
			->setSubSection(
				!empty($arParams['ANALYTICS']['c_sub_section']) && is_string($arParams['ANALYTICS']['c_sub_section'])
					? $arParams['ANALYTICS']['c_sub_section']
					: null
			)
			->setElement(\Bitrix\Crm\Integration\Analytics\Dictionary::ELEMENT_SETTINGS_BUTTON)
			->buildUri(
				CComponentEngine::makePathFromTemplate($arParams['PATH_TO_DEAL_DETAILS'], ['deal_id' => $arParams['ELEMENT_ID']])
			)
			->addParams([
				'copy' => 1,
			])
			->getUri()
		;

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('DEAL_COPY'),
			'TITLE' => GetMessage('DEAL_COPY_TITLE'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($copyUrl)."')",
			'ICON' => 'btn-copy'
		);
	}

	if($bWrite)
	{
		$moveToCategoryIDs = array_values(
			array_diff(
				\CCrmDeal::GetPermittedToMoveCategoryIDs(),
				array($arResult['CATEGORY_ID'])
			)
		);

		if(!empty($moveToCategoryIDs))
		{
			$arResult['CATEGORY_CHANGER'] = array(
				'ID' => "deal_category_change_{$arParams['ELEMENT_ID']}",
				'SERVICE_URL' => CComponentEngine::makePathFromTemplate(
					'/bitrix/components/bitrix/crm.deal.details/ajax.php?site_id=#SITE#&sessid=#SID#',
					array('SID' => bitrix_sessid())
				),
				'ENTITY_ID' => $arParams['ELEMENT_ID'],
				'CATEGORY_IDS' => $moveToCategoryIDs,
				'ACTION_NAME' => 'MOVE_TO_CATEGORY',
				'RELOAD' => true
			);

			$arResult['BUTTONS'][] = array(
				'TEXT' => GetMessage('DEAL_CHANGE_CATEGORY'),
				'TITLE' => GetMessage('DEAL_CHANGE_CATEGORY'),
				'ONCLICK' => "BX.Crm.DealCategoryChanger.getByEntityId({$arParams['ELEMENT_ID']}).process();",
				'ICON' => 'btn-convert'
			);
		}
	}

	if($bExclude && isset($scripts['EXCLUDE']))
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('DEAL_EXCLUDE'),
			'TITLE' => GetMessage('DEAL_EXCLUDE_TITLE'),
			'ONCLICK' => $scripts['EXCLUDE'],
		);
	}

	if($bDelete && isset($scripts['DELETE']))
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('DEAL_DELETE'),
			'TITLE' => GetMessage('DEAL_DELETE_TITLE'),
			'ONCLICK' => $scripts['DELETE'],
			'ICON' => 'btn-delete'
		);
	}

	if(\Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance()->isDocumentButtonAvailable())
	{
		$arResult['BUTTONS'][] = [
			'TEXT' => GetMessage('DOCUMENT_BUTTON_TEXT'),
			'TITLE' => GetMessage('DOCUMENT_BUTTON_TITLE'),
			'TYPE' => 'crm-document-button',
			'PARAMS' => \Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance()->getDocumentButtonParameters(\Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Deal::class, $arParams['ELEMENT_ID']),
		];
	}

	$this->IncludeComponentTemplate();
	return;
}

if($arParams['TYPE'] === 'list')
{
	$salesGeneratorButton = null;

	if ($bAdd)
	{
		$arResult['RC'] = [
			'CAN_USE' => Rc\Service::canUse(),
			'IS_AVAILABLE' => Rc\Service::isAvailable(),
			'NAME' => Rc\Service::getName(),
			'PATH_TO_ADD' => Rc\Service::getPathToAddDeal(),
			'JS_AVAILABLE_POPUP_SHOWER' => Rc\Service::getJsAvailablePopupShower(),
		];

		if ($arResult['RC']['CAN_USE'] && !$arResult['RC']['IS_AVAILABLE'])
		{
			Rc\Service::initJsExtensions();
		}

		$salesGeneratorButton = [
			'TEXT' => $arResult['RC']['NAME'],
			'ONCLICK' => $arResult['RC']['IS_AVAILABLE']
				? 'BX.SidePanel.Instance.open("' . \CUtil::JSEscape($arResult['RC']['PATH_TO_ADD']) . '")'
				: $arResult['RC']['JS_AVAILABLE_POPUP_SHOWER'],
			'CLASS_NAME' => $arResult['RC']['IS_AVAILABLE'] ? '' : 'b24-tariff-lock'
		];
	}

	$baseCreateUrl = CComponentEngine::MakePathFromTemplate(
		$arParams[$isSliderEnabled ? 'PATH_TO_DEAL_DETAILS' : 'PATH_TO_DEAL_EDIT'],
		['deal_id' => 0]
	);

	$categoryIDs = $arResult['CATEGORY_ID'] >= 0
		? [$arResult['CATEGORY_ID']]
		: CCrmDeal::GetPermittedToCreateCategoryIDs($CrmPerms);

	$categoryCount = count($categoryIDs);
	if ($categoryCount > 1)
	{
		$categories = DealCategory::getJavaScriptInfos($categoryIDs);
		$categoryButtons = [];
		foreach ($categories as $row)
		{
			$link = CCrmUrlUtil::AddUrlParams($baseCreateUrl, ['category_id' => $row['id']]);
			$categoryButton = [
				'ID' => $row['id'],
				'TITLE' => $row['name'],
				'TEXT' => $row['name'],
			];
			if($isSliderEnabled)
			{
				$categoryButton['ONCLICK'] = 'BX.SidePanel.Instance.open("' . CUtil::JSEscape($link) . '")';
			}
			else
			{
				$categoryButton['LINK'] = $link;
			}

			$categoryButtons[] = $categoryButton;
		}

		$categoryCreateUrl = '';
		$categorySelectorID = 'deal_category';
		$canCreateCategory = CCrmPerms::IsAdmin();
		if($canCreateCategory)
		{
			$restriction = RestrictionManager::getDealCategoryLimitRestriction();
			$limit = $restriction->getQuantityLimit();
			$canCreateCategory = $limit <= 0 || ($limit > DealCategory::getCount());
			if($canCreateCategory)
			{
				$categoryCreateUrl = CComponentEngine::MakePathFromTemplate(
					$arResult['PATH_TO_DEAL_CATEGORY_EDIT'],
					['category_id' => 0]
				);
			}
		}

		if (!empty($categoryCreateUrl))
		{
			$categoryButtons[] = [
				'TITLE' => GetMessage('DEAL_ADD_CATEGOTY'),
				'TEXT' => GetMessage('DEAL_ADD_CATEGOTY'),
				'URL' => $categoryCreateUrl,
			];
		}

		if (isset($salesGeneratorButton))
		{
			$categoryButtons[] = ["SEPARATOR" => true];
			$categoryButtons[] = $salesGeneratorButton;
		}

		// TODO: is not used (need check components crm.automation + crm.item.automation) and will be removed
		$arResult['CATEGORY_SELECTOR'] = [
			'ID' => $categorySelectorID,
			'CAN_CREATE_CATEGORY' => $canCreateCategory,
			'CATEGORY_LIST_URL' => $arResult['PATH_TO_DEAL_CATEGORY_LIST'],
			'CATEGORY_CREATE_URL' => $categoryCreateUrl,
			'CREATE_URL' => $baseCreateUrl,
			'INFOS' => $categories,
			'ENABLE_SLIDER' => $isSliderEnabled,
			'MESSAGES' => ['CREATE' => GetMessage('DEAL_ADD_CATEGOTY')],
		];

		$btnCfg = [
			'TEXT' => GetMessage('CRM_COMMON_ACTION_CREATE'),
			'TYPE' => 'crm-btn-double',
			'HIGHLIGHT' => true,
			'IS_DISABLED' => !$bAdd,
			'HINT' => GetMessage('CRM_DEAL_ADD_HINT'),
			'ITEMS' => $categoryButtons,

			// TODO: CrmDealCategorySelector (see common.js) is not used and will be removed
			//'ONCLICK' => "BX.CrmDealCategorySelector.items['{$categorySelectorID}'].openMenu(this)",
		];

		$mainButtonLink = CCrmUrlUtil::AddUrlParams($baseCreateUrl, ['category_id' => $categories[0]['id']]);
		if($isSliderEnabled)
		{
			$btnCfg['ONCLICK'] = 'BX.SidePanel.Instance.open("' . CUtil::JSEscape($mainButtonLink) . '")';
		}
		else
		{
			$btnCfg['LINK'] = $mainButtonLink;
		}

		$arResult['BUTTONS'][] = $btnCfg;
	}
	elseif($categoryCount === 1)
	{
		$link = \Bitrix\Crm\Integration\Analytics\Builder\Entity\AddOpenEvent::createDefault(\CCrmOwnerType::Deal)
			->setSection(
				!empty($arParams['ANALYTICS']['c_section']) && is_string($arParams['ANALYTICS']['c_section'])
					? $arParams['ANALYTICS']['c_section']
					: null
			)
			->setSubSection(
				!empty($arParams['ANALYTICS']['c_sub_section']) && is_string($arParams['ANALYTICS']['c_sub_section'])
					? $arParams['ANALYTICS']['c_sub_section']
					: null
			)
			->setElement(\Bitrix\Crm\Integration\Analytics\Dictionary::ELEMENT_CREATE_BUTTON)
			->buildUri($baseCreateUrl)
			->addParams(['category_id' => $categoryIDs[0]])
			->getUri()
		;

		if($arResult['RC']['CAN_USE'])
		{
			$itemAdd = ['TEXT' => GetMessage('DEAL_CREATE')];
			if($isSliderEnabled)
			{
				$itemAdd['ONCLICK'] = 'BX.SidePanel.Instance.open("' . CUtil::JSEscape($link) . '")';
			}
			else
			{
				$itemAdd['LINK'] = $link;
			}

			$arResult['BUTTONS'][] = [
				'TYPE' => 'crm-btn-double',
				'TEXT' => GetMessage('CRM_COMMON_ACTION_CREATE'),
				'LINK' => $link,
				'ITEMS' => [
					$itemAdd,
					$salesGeneratorButton
				],
				'HIGHLIGHT' => true,
				'IS_DISABLED' => !$bAdd,
				'HINT' => GetMessage('CRM_DEAL_ADD_HINT')
			];
		}
		else
		{
			$arResult['BUTTONS'][] = [
				'TEXT' => GetMessage('CRM_COMMON_ACTION_CREATE'),
				'TITLE' => GetMessage('CRM_COMMON_ACTION_CREATE'),
				'LINK' => $link,
				'HIGHLIGHT' => true,
				'IS_DISABLED' => !$bAdd,
				'HINT' => GetMessage('CRM_DEAL_ADD_HINT')
			];
		}
	}
	else
	{
		$arResult['BUTTONS'][] = [
			'TEXT' => GetMessage('CRM_COMMON_ACTION_CREATE'),
			'TITLE' => GetMessage('CRM_COMMON_ACTION_CREATE'),
			'LINK' => CCrmUrlUtil::AddUrlParams($baseCreateUrl, []),
			'HIGHLIGHT' => true,
			'IS_DISABLED' => !$bAdd,
			'HINT' => GetMessage('CRM_DEAL_ADD_HINT')
		];
	}

	if ($bImport && !$isInSlider)
	{
		$importUrl = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_IMPORT'], array());
		if($arResult['CATEGORY_ID'] >= 0)
		{
			$importUrl = CCrmUrlUtil::AddUrlParams($importUrl, array('category_id' => $arResult['CATEGORY_ID']));
		}

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('DEAL_IMPORT'),
			'TITLE' => GetMessage('DEAL_IMPORT_TITLE'),
			'LINK' => $importUrl,
			'ICON' => 'btn-import'
		);

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('DEAL_MIGRATION'),
			'TITLE' => GetMessage('DEAL_MIGRATION_TITLE'),
			'LINK' => $arParams['PATH_TO_MIGRATION'],
			'ICON' => 'btn-migration'
		);
	}
	if ($bExport && !$isInSlider)
	{
		if($bImport)
		{
			$arResult['BUTTONS'][] = array('SEPARATOR' => true);
		}

		$stExportId = 'EXPORT_'. \CCrmOwnerType::DealName;
		$componentName = 'bitrix:crm.deal.list';

		$componentParams = array(
			'DEAL_COUNT' => '20',
			'PATH_TO_DEAL_LIST' => $arParams['PATH_TO_DEAL_LIST'] ?? '',
			'PATH_TO_DEAL_SHOW' => $arParams['PATH_TO_DEAL_SHOW'] ?? '',
			'PATH_TO_DEAL_EDIT' => $arParams['PATH_TO_DEAL_EDIT'] ?? '',
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'] ?? '',
			'NAVIGATION_CONTEXT_ID' => \CCrmOwnerType::DealName,
			'IS_RECURRING' => $arParams['IS_RECURRING'],
			'PATH_TO_DEAL_RECUR' => $arResult['PATH_TO_DEAL_RECUR'] ?? '',
			'PATH_TO_DEAL_RECUR_SHOW' => $arResult['PATH_TO_DEAL_RECUR_SHOW'] ?? '',
			'PATH_TO_DEAL_RECUR_EDIT' => $arResult['PATH_TO_DEAL_RECUR_EDIT'] ?? '',
			'PATH_TO_DEAL_DETAILS' => $arResult['PATH_TO_DEAL_DETAILS'] ?? '',
			'PATH_TO_DEAL_WIDGET' => $arResult['PATH_TO_DEAL_WIDGET'] ?? '',
			'PATH_TO_DEAL_KANBAN' => $arResult['PATH_TO_DEAL_KANBAN'] ?? '',
			'PATH_TO_DEAL_CALENDAR' => $arResult['PATH_TO_DEAL_CALENDAR'] ?? '',
			'PATH_TO_DEAL_CATEGORY' => $arResult['PATH_TO_DEAL_CATEGORY'] ?? '',
			'PATH_TO_DEAL_RECUR_CATEGORY' => $arResult['PATH_TO_DEAL_RECUR_CATEGORY'] ?? '',
			'PATH_TO_DEAL_WIDGETCATEGORY' => $arResult['PATH_TO_DEAL_WIDGETCATEGORY'] ?? '',
			'PATH_TO_DEAL_KANBANCATEGORY' => $arResult['PATH_TO_DEAL_KANBANCATEGORY'] ?? '',
			'PATH_TO_DEAL_CALENDARCATEGORY' => $arResult['PATH_TO_DEAL_CALENDARCATEGORY'] ?? '',
			'GRID_ID_SUFFIX' => (new \Bitrix\Crm\Component\EntityList\GridId(CCrmOwnerType::Deal))
				->getDefaultSuffix($arResult['CATEGORY_ID']),
			'CATEGORY_ID' => $arResult['CATEGORY_ID'],
		);

		if (isset($_REQUEST['WG']) && mb_strtoupper($_REQUEST['WG']) === 'Y')
		{
			$widgetDataFilter = \Bitrix\Crm\Widget\Data\DealDataSource::extractDetailsPageUrlParams($_REQUEST);
			if (!empty($widgetDataFilter))
			{
				$componentParams['WIDGET_DATA_FILTER'] = $widgetDataFilter;
			}
		}

		$arResult['EXPORT_CSV_PARAMS'] = [
			'id' => $stExportId. '_CSV',
			'controller' => 'bitrix:crm.api.export',
			'queue' => [
				[
					'action' => 'dispatcher',
				],
			],
			'params' => [
				'SITE_ID' => SITE_ID,
				'ENTITY_TYPE' => \CCrmOwnerType::DealName,
				'EXPORT_TYPE' => 'csv',
				'COMPONENT_NAME' => $componentName,
				'signedParameters' => \Bitrix\Main\Component\ParameterSigner::signParameters($componentName, $componentParams),
			],
			'optionsFields' => array(
				'EXPORT_ALL_FIELDS' => array(
					'name' => 'EXPORT_ALL_FIELDS',
					'type' => 'checkbox',
					'title' => Loc::getMessage('DEAL_STEXPORT_OPTION_EXPORT_ALL_FIELDS'),
					'value' => 'N'
				),
				'EXPORT_ALL_CLIENT_FIELDS' => array(
					'name' => 'EXPORT_ALL_CLIENT_FIELDS',
					'type' => 'checkbox',
					'title' => Loc::getMessage('DEAL_STEXPORT_OPTION_EXPORT_ALL_CLIENT_FIELDS'),
					'value' => 'N'
				),
				'EXPORT_PRODUCT_FIELDS' => array(
					'name' => 'EXPORT_PRODUCT_FIELDS',
					'type' => 'checkbox',
					'title' => Loc::getMessage('DEAL_STEXPORT_OPTION_EXPORT_PRODUCT_FIELDS'),
					'value' => 'N'
				),
			),
			'messages' => array(
				'DialogTitle' => Loc::getMessage('DEAL_EXPORT_CSV_TITLE'),
				'DialogSummary' => Loc::getMessage('DEAL_STEXPORT_SUMMARY'),
			),
			'dialogMaxWidth' => 650,
		];

		// clone params for excel export
		$arResult['EXPORT_EXCEL_PARAMS'] = $arResult['EXPORT_CSV_PARAMS'];
		$arResult['EXPORT_EXCEL_PARAMS']['id'] = $stExportId. '_EXCEL';
		$arResult['EXPORT_EXCEL_PARAMS']['params']['EXPORT_TYPE'] = 'excel';
		$arResult['EXPORT_EXCEL_PARAMS']['messages']['DialogTitle'] = Loc::getMessage('DEAL_EXPORT_EXCEL_TITLE');

		$arResult['BUTTONS'][] = array('SEPARATOR' => true);

		$arResult['BUTTONS'][] = array(
			'TITLE' => Loc::getMessage('DEAL_EXPORT_CSV_TITLE'),
			'TEXT' => Loc::getMessage('DEAL_EXPORT_CSV'),
			'ONCLICK' => "BX.UI.StepProcessing.ProcessManager.get('{$stExportId}_CSV').showDialog()",
			'ICON' => 'btn-export'
		);

		$arResult['BUTTONS'][] = array(
			'TITLE' => Loc::getMessage('DEAL_EXPORT_EXCEL_TITLE'),
			'TEXT' => Loc::getMessage('DEAL_EXPORT_EXCEL'),
			'ONCLICK' => "BX.UI.StepProcessing.ProcessManager.get('{$stExportId}_EXCEL').showDialog()",
			'ICON' => 'btn-export'
		);

		$arResult['BUTTONS'][] = array('SEPARATOR' => true);

		unset($stExportId);
	}

	if (
		Recurring\Manager::isAllowedExpose(Recurring\Manager::DEAL)
		&& !$isInSlider
	)
	{
		if (isset($arParams['IS_RECURRING']) && $arParams['IS_RECURRING'] === 'Y')
		{
			$text = GetMessage('DEAL_LIST');
			$linkList = $arParams['PATH_TO_DEAL_LIST'];
			$linkCategoryList = $arParams['PATH_TO_DEAL_CATEGORY'];
		}
		else
		{
			$text = GetMessage('DEAL_CRM_RECURRING_LIST');
			$linkList = $arParams['PATH_TO_DEAL_RECUR'];
			$linkCategoryList = $arParams['PATH_TO_DEAL_RECUR_CATEGORY'];
		}

		$link = $arResult['CATEGORY_ID'] < 0
			? $linkList
			: CComponentEngine::makePathFromTemplate($linkCategoryList, array('category_id' => $arResult['CATEGORY_ID']));

		$arResult['BUTTONS'][] = [
			'TEXT' => $text,
			'TITLE' => $text,
			'ONCLICK' => 'BX.Crm.Page.openSlider("'.$link.'");'
		];
	}

	if(
		\Bitrix\Main\Loader::includeModule('rest')
		&& is_callable('\Bitrix\Rest\Marketplace\Url::getConfigurationPlacementUrl')
		&& ($bAdd || $bWrite || $bConfig)
		&& !$isInSlider
	)
	{
		$url = \Bitrix\Rest\Marketplace\Url::getConfigurationPlacementUrl('crm_deal', 'setting_list');
		$arResult['BUTTONS'][] = [
			'TEXT' => GetMessage('DEAL_VERTICAL_CRM'),
			'TITLE' => GetMessage('DEAL_VERTICAL_CRM_TITLE'),
			'ONCLICK' => 'BX.SidePanel.Instance.open(\''.$url.'\');'
		];
	}

	if ($bConfig && !$isInSlider)
	{
		CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/common.js');
		$arResult['BUTTONS'][] = \Bitrix\Crm\Settings\LeadSettings::getCrmTypeMenuItem(true);
		if (\CCrmSaleHelper::isWithOrdersMode())
		{
			$scenarioSelectionPath = CComponentEngine::makeComponentPath('bitrix:crm.scenario_selection');
			$scenarioSelectionPath = getLocalPath('components'.$scenarioSelectionPath.'/slider.php');
			$arResult['BUTTONS'][] = [
				'TEXT' => Loc::getMessage('DEAL_ORDER_SCENARIO'),
				'TITLE' => Loc::getMessage('DEAL_ORDER_SCENARIO'),
				'ONCLICK' => 'BX.SidePanel.Instance.open("' . $scenarioSelectionPath .'", {width: 900, cacheable: false});'
			];
		}
	}

	if(isset($arParams['ADDITIONAL_SETTINGS_MENU_ITEMS']) && is_array($arParams['ADDITIONAL_SETTINGS_MENU_ITEMS']) && !$isInSlider)
	{
		$arResult['BUTTONS'] = array_merge($arResult['BUTTONS'], $arParams['ADDITIONAL_SETTINGS_MENU_ITEMS']);
	}

	if(count($arResult['BUTTONS']) > 0)
	{
		//Force start new bar after add deal button or from first button
		array_splice($arResult['BUTTONS'], 1, 0, [['NEWBAR' => true]]);
	}

	$permissionItem = PermissionItem::createByEntity(CCrmOwnerType::Deal, $arResult['CATEGORY_ID']);
	if (isset($arParams['ANALYTICS']) && is_array($arParams['ANALYTICS']))
	{
		$permissionItem->setAnalytics($arParams['ANALYTICS']);
	}
	if ($permissionItem->canShow())
	{
		$arResult['BUTTONS'][] = $permissionItem->interfaceToolbarDelimiter();
		$arResult['BUTTONS'][] = $permissionItem->toInterfaceToolbarButton();
	}

	$this->IncludeComponentTemplate();

	return;
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show')
	&& !empty($arParams['ELEMENT_ID'])
	&& $bWrite
	&& $arParams['IS_RECURRING'] !== 'Y'
)
{
	$plannerButton = \Bitrix\Crm\Activity\Planner::getToolbarButton($arParams['ELEMENT_ID'], CCrmOwnerType::Deal);
	if($plannerButton)
	{
		CJSCore::Init(array('crm_activity_planner'));
		$arResult['BUTTONS'][] = $plannerButton;
	}
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show')
	&& !empty($arParams['ELEMENT_ID'])
	&& CCrmDeal::CheckConvertPermission($arParams['ELEMENT_ID'], CCrmOwnerType::Undefined, $CrmPerms)
	&& $arParams['IS_RECURRING'] !== 'Y'
)
{
	$schemeID = \Bitrix\Crm\Conversion\DealConversionConfig::getCurrentSchemeID();
	$arResult['BUTTONS'][] = array(
		'TYPE' => 'toolbar-conv-scheme',
		'PARAMS' => array(
			'NAME' => 'deal_converter',
			'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
			'ENTITY_TYPE_NAME' => CCrmOwnerType::DealName,
			'ENTITY_ID' => $arParams['ELEMENT_ID'],
			'SCHEME_ID' => $schemeID,
			'SCHEME_NAME' => \Bitrix\Crm\Conversion\DealConversionScheme::resolveName($schemeID),
			'SCHEME_DESCRIPTION' => \Bitrix\Crm\Conversion\DealConversionScheme::getDescription($schemeID),
			'IS_PERMITTED' => true
		),
		'CODE' => 'convert',
		'TEXT' => GetMessage('DEAL_CREATE_ON_BASIS'),
		'TITLE' => GetMessage('DEAL_CREATE_ON_BASIS_TITLE_MSGVER_1'),
		'ICON' => $isPermitted ? 'btn-convert' : 'btn-convert-blocked'
	);
}

if ($arParams['TYPE'] == 'show' && $arParams['IS_RECURRING'] === 'Y' && $bAdd)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('DEAL_EXPOSE'),
		'TITLE' => GetMessage('DEAL_EXPOSE_TITLE'),
		'LINK' => CComponentEngine::makePathFromTemplate($arParams['PATH_TO_DEAL_RECUR_EXPOSE'],
			array(
				'deal_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-convert'
	);
}

if (($arParams['TYPE'] == 'show') && $bRead && $arParams['ELEMENT_ID'] > 0 && $arParams['IS_RECURRING'] !== 'Y')
{
	$subscrTypes = CCrmSonetSubscription::GetRegistationTypes(
		CCrmOwnerType::Deal,
		$arParams['ELEMENT_ID'],
		$currentUserID
	);

	$isResponsible = in_array(CCrmSonetSubscriptionType::Responsibility, $subscrTypes, true);
	if(!$isResponsible)
	{
		$subscriptionID = 'deal_sl_subscribe';
		$arResult['SONET_SUBSCRIBE'] = array(
			'ID' => $subscriptionID,
			'SERVICE_URL' => CComponentEngine::makePathFromTemplate(
				'/bitrix/components/bitrix/crm.deal.edit/ajax.php?site_id=#SITE#&sessid=#SID#',
				array('SID' => bitrix_sessid())
			),
			'ACTION_NAME' => 'ENABLE_SONET_SUBSCRIPTION',
			'RELOAD' => true
		);

		$isObserver = in_array(CCrmSonetSubscriptionType::Observation, $subscrTypes, true);
		$arResult['BUTTONS'][] = array(
			'CODE' => 'sl_unsubscribe',
			'TEXT' => GetMessage('CRM_DEAL_SL_UNSUBSCRIBE'),
			'TITLE' => GetMessage('CRM_DEAL_SL_UNSUBSCRIBE_TITLE'),
			'ONCLICK' => "BX.CrmSonetSubscription.items['{$subscriptionID}'].unsubscribe({$arParams['ELEMENT_ID']}, function(){ var tb = BX.InterfaceToolBar.items['{$toolbarID}']; tb.setButtonVisible('sl_unsubscribe', false); tb.setButtonVisible('sl_subscribe', true); })",
			'ICON' => 'btn-nofollow',
			'VISIBLE' => $isObserver
		);
		$arResult['BUTTONS'][] = array(
			'CODE' => 'sl_subscribe',
			'TEXT' => GetMessage('CRM_DEAL_SL_SUBSCRIBE'),
			'TITLE' => GetMessage('CRM_DEAL_SL_SUBSCRIBE_TITLE'),
			'ONCLICK' => "BX.CrmSonetSubscription.items['{$subscriptionID}'].subscribe({$arParams['ELEMENT_ID']}, function(){ var tb = BX.InterfaceToolBar.items['{$toolbarID}']; tb.setButtonVisible('sl_subscribe', false); tb.setButtonVisible('sl_unsubscribe', true); })",
			'ICON' => 'btn-follow',
			'VISIBLE' => !$isObserver
		);
	}
}

if ($arParams['TYPE'] == 'show' && !empty($arParams['ELEMENT_ID']))
{
	if($bWrite)
	{
		$path = $arParams['IS_RECURRING'] === 'Y' ? $arParams['PATH_TO_DEAL_RECUR_EDIT'] : $arParams['PATH_TO_DEAL_EDIT'];
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('DEAL_EDIT'),
			'TITLE' => GetMessage('DEAL_EDIT_TITLE'),
			'LINK' => CComponentEngine::makePathFromTemplate($path,
				array(
					'deal_id' => $arParams['ELEMENT_ID']
				)
			),
			'ICON' => 'btn-edit'
		);
	}
}

if ($arParams['TYPE'] == 'edit' && $bRead && !empty($arParams['ELEMENT_ID']))
{
	$path = $arParams['IS_RECURRING'] === 'Y' ? $arParams['PATH_TO_DEAL_RECUR_SHOW'] : $arParams['PATH_TO_DEAL_SHOW'];
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('DEAL_SHOW'),
		'TITLE' => GetMessage('DEAL_SHOW_TITLE'),
		'LINK' => CComponentEngine::makePathFromTemplate($path,
			array(
				'deal_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-view'
	);
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $bAdd
	&& !empty($arParams['ELEMENT_ID']) && !isset($_REQUEST['copy']) && $arParams['IS_RECURRING'] !== 'Y')
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('DEAL_COPY'),
		'TITLE' => GetMessage('DEAL_COPY_TITLE'),
		'LINK' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_EDIT'],
			array(
				'deal_id' => $arParams['ELEMENT_ID']
			)),
			array('copy' => 1)
		),
		'ICON' => 'btn-copy'
	);
}

$qty = count($arResult['BUTTONS']);

if (!empty($arResult['BUTTONS']) && $arParams['TYPE'] == 'edit' && empty($arParams['ELEMENT_ID']))
	$arResult['BUTTONS'][] = array('SEPARATOR' => true);
elseif ($arParams['TYPE'] == 'show' && $qty > 1)
	$arResult['BUTTONS'][] = array('NEWBAR' => true);
elseif ($qty >= 3)
	$arResult['BUTTONS'][] = array('NEWBAR' => true);

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $bDelete && !empty($arParams['ELEMENT_ID']))
{
	$path = ($arParams['IS_RECURRING'] === 'Y') ? $arParams['PATH_TO_DEAL_RECUR_EDIT'] : $arParams['PATH_TO_DEAL_EDIT'];
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('DEAL_DELETE'),
		'TITLE' => GetMessage('DEAL_DELETE_TITLE'),
		'LINK' => "javascript:deal_delete('".GetMessage('DEAL_DELETE_DLG_TITLE')."', '".GetMessage('DEAL_DELETE_DLG_MESSAGE')."', '".GetMessage('DEAL_DELETE_DLG_BTNTITLE')."', '".CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($path,
			array(
				'deal_id' => $arParams['ELEMENT_ID']
			)),
			array('delete' => '', 'sessid' => bitrix_sessid())
		)."')",
		'ICON' => 'btn-delete'
	);
}

if ($bAdd && $arParams['TYPE'] != 'list')
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_COMMON_ACTION_CREATE'),
		'LINK' => CCrmUrlUtil::AddUrlParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams[$isSliderEnabled ? 'PATH_TO_DEAL_DETAILS' : 'PATH_TO_DEAL_EDIT'],
				array('deal_id' => 0)
			),
			array('category_id' => $arResult['CATEGORY_ID'])
		),
		'TARGET' => '_blank',
		'ICON' => 'btn-new'
	);
}

$this->IncludeComponentTemplate();

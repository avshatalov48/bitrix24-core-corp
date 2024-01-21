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

use Bitrix\Crm\Order\Permissions;
use Bitrix\Crm\Service;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

if (!CModule::IncludeModule('crm'))
{
	return;
}

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$CrmPerms = CCrmPerms::GetCurrentUserPermissions();
if (!Permissions\Order::checkReadPermission(0, $CrmPerms))
{
	return;
}

$arParams['PATH_TO_ORDER_LIST'] = CrmCheckPath(
	'PATH_TO_ORDER_LIST',
	$arParams['PATH_TO_ORDER_LIST'] ?? '',
	$APPLICATION->GetCurPage()
);

$arParams['PATH_TO_ORDER_EDIT'] = CrmCheckPath(
	'PATH_TO_ORDER_EDIT',
	$arParams['PATH_TO_ORDER_EDIT'] ?? '',
	$APPLICATION->GetCurPage() . '?order_id=#order_id#&edit'
);

$arParams['PATH_TO_ORDER_IMPORT'] = CrmCheckPath(
	'PATH_TO_ORDER_IMPORT',
	$arParams['PATH_TO_ORDER_IMPORT'] ?? '',
	$APPLICATION->GetCurPage() . '?import'
);

$arParams['PATH_TO_MIGRATION'] = \Bitrix\Crm\Integration\Market\Router::getCategoryPath('migration');

$arParams['PATH_TO_ORDER_FORM'] = CrmCheckPath(
	'PATH_TO_ORDER_FORM',
	$arParams['PATH_TO_ORDER_FORM'] ?? '',
	$APPLICATION->GetCurPage()
);

$arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? (int)$arParams['ELEMENT_ID'] : 0;
$arResult['CONVERSION_PERMITTED'] = $arParams['CONVERSION_PERMITTED'] ?? true;

if (!isset($arParams['TYPE']))
{
	$arParams['TYPE'] = 'list';
}

if (isset($_REQUEST['copy']))
{
	$arParams['TYPE'] = 'copy';
}

$toolbarID = 'toolbar_order_' . $arParams['TYPE'];
if ($arParams['ELEMENT_ID'] > 0)
{
	$toolbarID .= '_'.$arParams['ELEMENT_ID'];
}
$arResult['TOOLBAR_ID'] = $toolbarID;

$arResult['BUTTONS'] = [];

if ($arParams['TYPE'] === 'list' || $arParams['TYPE'] === 'kanban')
{
	$bRead = Permissions\Order::checkReadPermission(0, $CrmPerms);
	$bExport = Permissions\Order::checkExportPermission($CrmPerms);
	$bImport = Permissions\Order::checkImportPermission($CrmPerms);
	$bAdd = Permissions\Order::checkCreatePermission($CrmPerms);
	$bWrite = Permissions\Order::checkUpdatePermission(0, $CrmPerms);
	$bDelete = false;
	$bConfig = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
}
else
{
	$bExport = false;
	$bImport = false;

	$bRead = Permissions\Order::checkReadPermission($arParams['ELEMENT_ID'], $CrmPerms);
	$bAdd = Permissions\Order::checkCreatePermission($CrmPerms);
	$bWrite = Permissions\Order::checkUpdatePermission($arParams['ELEMENT_ID'], $CrmPerms);
	$bDelete = Permissions\Order::checkDeletePermission($arParams['ELEMENT_ID'], $CrmPerms);
}

if (isset($arParams['DISABLE_EXPORT']) && $arParams['DISABLE_EXPORT'] === 'Y')
{
	$bExport = false;
}

if (!$bRead && !$bAdd && !$bWrite)
{
	return false;
}

//Skip COPY menu in slider mode
if ($arParams['TYPE'] === 'copy' && \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled())
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

	if (!empty($arParams['BIZPROC_STARTER_DATA']))
	{
		$arResult['BUTTONS'][] = array(
			'TYPE' => 'bizproc-starter-button',
			'DATA' => $arParams['BIZPROC_STARTER_DATA']
		);
	}

	\Bitrix\Crm\Order\Permissions\Order::prepareConversionPermissionFlags($arParams['ELEMENT_ID'], $arResult, $CrmPerms);

	//Force start new bar after first button
	$arResult['BUTTONS'][] = array('NEWBAR' => true);

	if ($bWrite)
	{
		$arResult['BUTTONS'][] = array(
			'TYPE' => 'crm-communication-panel',
			'DATA' => array(
				'ENABLE_CALL' => \Bitrix\Main\ModuleManager::isModuleInstalled('calendar'),
				'OWNER_INFO' => $arParams['OWNER_INFO'] ?? [],
				'MULTIFIELDS' => $arParams['MULTIFIELD_DATA'] ?? [],
			)
		);
	}

	if ($bAdd)
	{
		$copyUrl = CHTTP::urlAddParams(
			Service\Sale\EntityLinkBuilder\EntityLinkBuilder::getInstance()->getOrderDetailsLink(
				$arParams['ELEMENT_ID'],
				Service\Sale\EntityLinkBuilder\Context::getShopAreaContext()
			),
			array('copy' => 1)
		);

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('ORDER_COPY'),
			'TITLE' => GetMessage('ORDER_COPY_TITLE'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($copyUrl)."')",
			'ICON' => 'btn-copy'
		);
	}

	if ($bDelete && isset($scripts['DELETE']))
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('ORDER_DELETE'),
			'TITLE' => GetMessage('ORDER_DELETE_TITLE'),
			'ONCLICK' => $scripts['DELETE'],
			'ICON' => 'btn-delete'
		);
	}

	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('ORDER_EDIT_ORDER_FORM'),
		'TITLE' => GetMessage('ORDER_EDIT_ORDER_FORM_TITLE'),
		'ONCLICK' => "BX.SidePanel.Instance.open('"
			.CUtil::JSEscape(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ORDER_FORM']))
			."', {loader: \"crm-webform-view-loader\"})",
		'ICON' => 'btn-edit'
	);

	if (\Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance()->isDocumentButtonAvailable())
	{
		$arResult['BUTTONS'][] = [
			'CODE' => 'document',
			'TEXT' => GetMessage('ORDER_DOCUMENT_BUTTON_TEXT'),
			'TITLE' => GetMessage('ORDER_DOCUMENT_BUTTON_TITLE'),
			'TYPE' => 'crm-document-button',
			'PARAMS' => \Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance()
				->getDocumentButtonParameters(
					\Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Order::class,
					$arParams['ELEMENT_ID']
				),
		];
	}

	$this->IncludeComponentTemplate();

	return;
}

$sites = [];
$sitesTmp = [];
$res = \Bitrix\Main\SiteTable::getList([
	'filter' => ['=ACTIVE' => 'Y', '!LID' => 'ex'],
	'order' => ['SORT' => 'ASC', 'LID' => 'ASC']
]);

while($site = $res->fetch())
{
	$optSite = Option::get("sale", "SHOP_SITE_".$site['LID'], "");

	if ($site['LID'] == $optSite)
	{
		$sites[$site['LID']] = '['.$site['LID'].'] '.htmlspecialcharsbx($site['NAME']);
	}
	else
	{
		$sitesTmp[$site['LID']] = '['.$site['LID'].'] '.htmlspecialcharsbx($site['NAME']);
	}
}

if (count($sites) <= 0)
{
	$sites = $sitesTmp;
}

if ($bAdd)
{
	$link = CCrmUrlUtil::AddUrlParams(
		Service\Sale\EntityLinkBuilder\EntityLinkBuilder::getInstance()->getOrderDetailsLink(
			0,
			Service\Sale\EntityLinkBuilder\Context::getShopAreaContext()
		),
		array('SITE_ID' => key($sites))
	);

	if (count($sites) === 1)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('ORDER_ADD'),
			'TITLE' => GetMessage('ORDER_ADD_TITLE'),
			'LINK' => $link,
			'HIGHLIGHT' => true,
		);
	}
	else
	{
		$items = [];

		foreach ($sites as  $lid => $name)
		{
			$onClickHandler = 'BX.SidePanel.Instance.open(\'' .
				CCrmUrlUtil::AddUrlParams(
					Service\Sale\EntityLinkBuilder\EntityLinkBuilder::getInstance()->getOrderDetailsLink(
						0,
						Service\Sale\EntityLinkBuilder\Context::getShopAreaContext()
					),
					array('SITE_ID' => $lid)
				) . '\')';

			$items[] = array(
				'TEXT' => $name,
				'ONCLICK' => $onClickHandler,
			);
		}

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('ORDER_ADD'),
			'TITLE' => GetMessage('ORDER_ADD_TITLE'),
			'LINK' => $link,
			'TYPE' => 'crm-btn-double',
			'ITEMS' => $items,
			'HIGHLIGHT' => true,
		);
	}
}

if ($arParams['TYPE'] === 'list')
{
	if ($bExport)
	{
		$arResult['BUTTONS'][] = ['NEWBAR' => true];

		$stExportId = 'EXPORT_'.\CCrmOwnerType::OrderName;
		$componentName = 'bitrix:crm.order.list';

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
				'ENTITY_TYPE' => \CCrmOwnerType::OrderName,
				'EXPORT_TYPE' => 'csv',
				'COMPONENT_NAME' => $componentName,
				'signedParameters' => \Bitrix\Main\Component\ParameterSigner::signParameters(
					$componentName,
					array(
						'ORDER_COUNT' => '20',
						'PATH_TO_ORDER_SHOW' => $arResult['PATH_TO_ORDER_SHOW'] ?? '',
						'PATH_TO_ORDER_EDIT' => $arResult['PATH_TO_ORDER_EDIT'] ?? '',
						'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'] ?? '',
						'NAVIGATION_CONTEXT_ID' => \CCrmOwnerType::OrderName
					)
				),
			],
			'optionsFields' => array(
				'EXPORT_ALL_FIELDS' => array(
					'name' => 'EXPORT_ALL_FIELDS',
					'type' => 'checkbox',
					'title' => Loc::getMessage('ORDER_STEXPORT_OPTION_EXPORT_ALL_FIELDS'),
					'value' => 'N'
				),
			),
			'messages' => array(
				'DialogTitle' => Loc::getMessage('ORDER_EXPORT_CSV_TITLE'),
				'DialogSummary' => Loc::getMessage('ORDER_STEXPORT_SUMMARY'),
			),
			'dialogMaxWidth' => 650,
		];

		// clone params for excel export
		$arResult['EXPORT_EXCEL_PARAMS'] = $arResult['EXPORT_CSV_PARAMS'];
		$arResult['EXPORT_EXCEL_PARAMS']['id'] = $stExportId. '_EXCEL';
		$arResult['EXPORT_EXCEL_PARAMS']['params']['EXPORT_TYPE'] = 'excel';
		$arResult['EXPORT_EXCEL_PARAMS']['messages']['DialogTitle'] = Loc::getMessage('ORDER_EXPORT_EXCEL_TITLE');

		$arResult['BUTTONS'][] = array(
			'TITLE' => Loc::getMessage('ORDER_EXPORT_CSV_TITLE'),
			'TEXT' => Loc::getMessage('ORDER_EXPORT_CSV'),
			'ONCLICK' => "BX.UI.StepProcessing.ProcessManager.get('{$stExportId}_CSV').showDialog()",
			'ICON' => 'btn-export'
		);

		$arResult['BUTTONS'][] = array(
			'TITLE' => Loc::getMessage('ORDER_EXPORT_EXCEL_TITLE'),
			'TEXT' => Loc::getMessage('ORDER_EXPORT_EXCEL'),
			'ONCLICK' => "BX.UI.StepProcessing.ProcessManager.get('{$stExportId}_EXCEL').showDialog()",
			'ICON' => 'btn-export'
		);

		unset($stExportId);
	}
}

if ($bConfig && \CCrmSaleHelper::isWithOrdersMode())
{
	$arResult['BUTTONS'][] = array('NEWBAR' => true);
	$scenarioSelectionPath = CComponentEngine::makeComponentPath('bitrix:crm.scenario_selection');
	$scenarioSelectionPath = getLocalPath('components'.$scenarioSelectionPath.'/slider.php');
	$arResult['BUTTONS'][] = [
		'TEXT' => Loc::getMessage('DEAL_ORDER_SCENARIO'),
		'TITLE' => Loc::getMessage('DEAL_ORDER_SCENARIO'),
		'ONCLICK' => 'BX.SidePanel.Instance.open("' . $scenarioSelectionPath .'", {width: 900, cacheable: false});'
	];
}

$qty = count($arResult['BUTTONS']);
if ($arParams['TYPE'] === 'kanban' && $GLOBALS['USER']->canDoOperation('edit_other_settings'))
{
	$arResult['BUTTONS'][] = array('NEWBAR' => true);
	$arResult['BUTTONS'][] = array('NEWBAR' => true);
}

if ($qty >= 3)
{
	$arResult['BUTTONS'][] = array('NEWBAR' => true);
}

$this->IncludeComponentTemplate();

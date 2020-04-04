<?php

use \Bitrix\Crm\Order\Permissions,
	\Bitrix\Main\Config\Option;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
	return;

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$CrmPerms = CCrmPerms::GetCurrentUserPermissions();
if(!Permissions\Order::checkReadPermission(0, $CrmPerms))
	return;

$arParams['PATH_TO_ORDER_LIST'] = CrmCheckPath('PATH_TO_ORDER_LIST', $arParams['PATH_TO_ORDER_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_ORDER_EDIT'] = CrmCheckPath('PATH_TO_ORDER_EDIT', $arParams['PATH_TO_ORDER_EDIT'], $APPLICATION->GetCurPage().'?order_id=#order_id#&edit');
$arParams['PATH_TO_ORDER_DETAILS'] = CrmCheckPath('PATH_TO_ORDER_DETAILS', $arParams['PATH_TO_ORDER_DETAILS'], $APPLICATION->GetCurPage().'?order_id=#order_id#&details');
$arParams['PATH_TO_ORDER_IMPORT'] = CrmCheckPath('PATH_TO_ORDER_IMPORT', $arParams['PATH_TO_ORDER_IMPORT'], $APPLICATION->GetCurPage().'?import');
$arParams['PATH_TO_MIGRATION'] = SITE_DIR."marketplace/category/migration/";
$arParams['PATH_TO_ORDER_FORM'] = CrmCheckPath('PATH_TO_ORDER_FORM', $arParams['PATH_TO_ORDER_FORM'], $APPLICATION->GetCurPage());

$arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? (int)$arParams['ELEMENT_ID'] : 0;
$arResult['CONVERSION_PERMITTED'] = isset($arParams['CONVERSION_PERMITTED']) ? $arParams['CONVERSION_PERMITTED'] : true;

if (!isset($arParams['TYPE']))
	$arParams['TYPE'] = 'list';

if (isset($_REQUEST['copy']))
	$arParams['TYPE'] = 'copy';

$toolbarID = 'toolbar_order_'.$arParams['TYPE'];
if($arParams['ELEMENT_ID'] > 0)
{
	$toolbarID .= '_'.$arParams['ELEMENT_ID'];
}
$arResult['TOOLBAR_ID'] = $toolbarID;

$arResult['BUTTONS'] = array();

if ($arParams['TYPE'] == 'list')
{
	$bRead   = Permissions\Order::checkReadPermission(0, $CrmPerms);
	$bExport = Permissions\Order::checkExportPermission($CrmPerms);
	$bImport = Permissions\Order::checkImportPermission($CrmPerms);
	$bAdd    = Permissions\Order::checkCreatePermission($CrmPerms);
	$bWrite  = Permissions\Order::checkUpdatePermission(0, $CrmPerms);
	$bDelete = false;
	$bConfig = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
}
else
{
	$bExport = false;
	$bImport = false;

	$bRead   = Permissions\Order::checkReadPermission($arParams['ELEMENT_ID'], $CrmPerms);
	$bAdd    = Permissions\Order::checkCreatePermission($CrmPerms);
	$bWrite  = Permissions\Order::checkUpdatePermission($arParams['ELEMENT_ID'], $CrmPerms);
	$bDelete = Permissions\Order::checkDeletePermission($arParams['ELEMENT_ID'], $CrmPerms);
}

if (isset($arParams['DISABLE_EXPORT']) && $arParams['DISABLE_EXPORT'] == 'Y')
{
	$bExport = false;
}

if (!$bRead && !$bAdd && !$bWrite)
	return false;

//Skip COPY menu in slider mode
if($arParams['TYPE'] == 'copy' && \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled())
{
	return false;
}

if($arParams['TYPE'] === 'details')
{
	if($arParams['ELEMENT_ID'] <= 0)
	{
		return false;
	}

	$scripts = isset($arParams['~SCRIPTS']) && is_array($arParams['~SCRIPTS']) ? $arParams['~SCRIPTS'] : array();

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

	if($bWrite)
	{
		$arResult['BUTTONS'][] = array(
			'TYPE' => 'crm-communication-panel',
			'DATA' => array(
				'ENABLE_CALL' => \Bitrix\Main\ModuleManager::isModuleInstalled('calendar'),
				'OWNER_INFO' => isset($arParams['OWNER_INFO']) ? $arParams['OWNER_INFO'] : array(),
				'MULTIFIELDS' => isset($arParams['MULTIFIELD_DATA']) ? $arParams['MULTIFIELD_DATA'] : array()
			)
		);
	}

	if($bAdd)
	{
		$copyUrl = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_ORDER_DETAILS'],
				array('order_id' => $arParams['ELEMENT_ID'])
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

	if($bDelete && isset($scripts['DELETE']))
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

	$this->IncludeComponentTemplate();
	return;
}

$sites = array();
$sitesTmp = array();
$res = \Bitrix\Main\SiteTable::getList(array(
	'filter' => array('=ACTIVE' => 'Y'),
	'order' => array('SORT' => 'ASC', 'LID' => 'ASC')
));

while($site = $res->fetch())
{
	$optSite = Option::get("sale", "SHOP_SITE_".$site['LID'], "");

	if($site['LID'] == $optSite)
	{
		$sites[$site['LID']] = '['.$site['LID'].'] '.htmlspecialcharsbx($site['NAME']);
	}
	else
	{
		$sitesTmp[$site['LID']] = '['.$site['LID'].'] '.htmlspecialcharsbx($site['NAME']);
	}
}

if(count($sites) <= 0)
{
	$sites = $sitesTmp;
}

if($bAdd)
{
	$link = CCrmUrlUtil::AddUrlParams(
		CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_ORDER_DETAILS'],
			array('order_id' => 0)
		),
		array('SITE_ID' => key($sites))
	);

	if(count($sites) == 1)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('ORDER_ADD'),
			'TITLE' => GetMessage('ORDER_ADD_TITLE'),
			'LINK' => $link,
			'HIGHLIGHT' => true
		);
	}
	else
	{
		$items = array();

		foreach($sites as  $lid => $name)
		{
			$items[] = array(
				'TEXT' => $name,
				'ONCLICK' => 'BX.SidePanel.Instance.open(\''.
					CCrmUrlUtil::AddUrlParams(
						CComponentEngine::MakePathFromTemplate(
							$arParams['PATH_TO_ORDER_DETAILS'],
							array('order_id' => 0)
						),
						array('SITE_ID' => $lid)
				).'\')'
			);
		}

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('ORDER_ADD'),
			'TITLE' => GetMessage('ORDER_ADD_TITLE'),
			'LINK' => $link,
			'TYPE' => 'crm-btn-double',
			'ITEMS' => $items,
			'HIGHLIGHT' => true
		);
	}
}

$qty = count($arResult['BUTTONS']);

if ($qty >= 3)
{
	$arResult['BUTTONS'][] = array('NEWBAR' => true);
}

$this->IncludeComponentTemplate();
?>

<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if(!CAllCrmInvoice::installExternalEntities())
	return;


if (!CModule::IncludeModule('iblock'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_IBLOCK'));
	return;
}
if (!CModule::IncludeModule('currency'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY'));
	return;
}
if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_SALE'));
	return;
}
if (!CModule::IncludeModule('catalog'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CATALOG'));
	return;
}

global $APPLICATION, $USER;
$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['PATH_TO_EXCH1C_INDEX'] = CrmCheckPath('PATH_TO_EXCH1C_INDEX', $arParams['PATH_TO_EXCH1C_INDEX'], $APPLICATION->GetCurPage());
$arResult['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
$arResult['BACK_URL'] = $arParams['PATH_TO_EXCH1C_INDEX'];
$arResult['FORM_ID'] = 'CRM_EXCH1C_CONFIG';
$arResult['FIELDS'] = array();
$arResult['FIELDS_CONFIG'] = array();
$arResult['FIELDS_DATA'] = array();

$arResult['FIELDS_CONFIG'] = array(
	array(
		'name' => 'tab_catalog_import',
		'title' => GetMessage('CRM_TAB_CATALOG_IMPORT'),
		'type' => 'section',
		'elements' => array()
	),
	array(
		'name' => 'tab_catalog_export',
		'title' => GetMessage('CRM_TAB_CATALOG_EXPORT'),
		'type' => 'section',
		'elements' => array()
	)
);


// <editor-fold defaultstate="collapsed" desc="Catalog import options">
// --> Catalog import options
$arUGroupsEx = Array();
$arAction = array(
	"N" => GetMessage("CAT_1C_NONE"),
	"A" => GetMessage("CAT_1C_DEACTIVATE"),
	"D" => GetMessage("CAT_1C_DELETE"),
);
$iblockType = CCrmCatalog::GetCatalogTypeID();
$iblockId = CCrmCatalog::EnsureDefaultExists();

$arPriceType = array();
$arGroup = array();
$rsGroup = CCatalogGroup::GetListEx(
	array('SORT', 'ASC'),
	array(),
	false,
	false,
	array('ID', 'NAME', 'NAME_LANG', 'XML_ID')
);
while ($arGroup = $rsGroup->Fetch())
{
	$arPriceType[intval($arGroup['ID'])] = '['.$arGroup['ID'].'] '.$arGroup['NAME'].((empty($arGroup['NAME_LANG'])) ? '' : ', '.$arGroup['NAME_LANG']);
}
unset($arGroup, $rsGroup);

$priceTypeId = intval(COption::GetOptionInt('crm', 'selected_catalog_group_id', 0));
if ($priceTypeId < 1)
{
	$arBaseCatalogGroup = CCatalogGroup::GetBaseGroup();
	$priceTypeId = intval($arBaseCatalogGroup['ID']);
	unset($arBaseCatalogGroup);
}

$arAllOptions = array(
	// import tab
	array("1C_IBLOCK_TYPE", GetMessage("CAT_1C_IBLOCK_TYPE"), $iblockType, Array("text", 50), "hidden", 1),
	array("1C_SITE_LIST", GetMessage("CAT_1C_SITE_LIST"), SITE_ID, Array("text", 2), "hidden", 1),
	array("1C_INTERVAL", GetMessage("CAT_1C_INTERVAL"), "30", Array("text", 20), "visible", 1),
	array("1C_GROUP_PERMISSIONS", GetMessage("CAT_1C_GROUP_PERMISSIONS"), "-", Array("mlist", 5, $arUGroupsEx), "hidden", 1),
	array("1C_ELEMENT_ACTION", GetMessage("CAT_1C_ELEMENT_ACTION"), "D", Array("list", $arAction), "visible", 1),
	array("1C_SECTION_ACTION", GetMessage("CAT_1C_SECTION_ACTION"), "D", Array("list", $arAction), "visible", 1),
	array("1C_FILE_SIZE_LIMIT", GetMessage("CAT_1C_FILE_SIZE_LIMIT"), 200*1024, Array("text", 20), "hidden", 1),
	array("1C_USE_CRC", GetMessage("CAT_1C_USE_CRC"), "Y", Array("checkbox"), "hidden", 1),
	array("1C_USE_ZIP", GetMessage("CAT_1C_USE_ZIP"), "Y", Array("checkbox"), "hidden", 1),
	array("1C_USE_IBLOCK_PICTURE_SETTINGS", GetMessage("CAT_1C_USE_IBLOCK_PICTURE_SETTINGS"), "N", Array("checkbox"), "hidden", 1),
	array("1C_GENERATE_PREVIEW", GetMessage("CAT_1C_GENERATE_PREVIEW"), "N", Array("checkbox"), "hidden", 1),
	array("1C_PREVIEW_WIDTH", GetMessage("CAT_1C_PREVIEW_WIDTH"), 100, Array("text", 20), "hidden", 1),
	array("1C_PREVIEW_HEIGHT", GetMessage("CAT_1C_PREVIEW_HEIGHT"), 100, Array("text", 20), "hidden", 1),
	array("1C_DETAIL_RESIZE", GetMessage("CAT_1C_DETAIL_RESIZE"), "N", Array("checkbox"), "hidden", 1),
	array("1C_DETAIL_WIDTH", GetMessage("CAT_1C_DETAIL_WIDTH"), 300, Array("text", 20), "hidden", 1),
	array("1C_DETAIL_HEIGHT", GetMessage("CAT_1C_DETAIL_HEIGHT"), 300, Array("text", 20), "hidden", 1),
	array("1C_USE_OFFERS", GetMessage("CAT_1C_USE_OFFERS"), "N", Array("checkbox"), "hidden", 1),
	array("1C_FORCE_OFFERS", GetMessage("CAT_1C_FORCE_OFFERS"), "N", Array("checkbox"), "hidden", 1),
	array("1C_USE_IBLOCK_TYPE_ID", GetMessage("CAT_1C_USE_IBLOCK_TYPE_ID"), "N", Array("checkbox"), "hidden", 1),
	array("1C_SKIP_ROOT_SECTION", GetMessage("CAT_1C_SKIP_ROOT_SECTION"), "N", Array("checkbox"), "hidden", 1),
	array("1C_TRANSLIT_ON_ADD", GetMessage("CAT_1C_TRANSLIT_ON_ADD"), "N", Array("checkbox"), "hidden", 1),
	array("1C_TRANSLIT_ON_UPDATE", GetMessage("CAT_1C_TRANSLIT_ON_UPDATE"), "N", Array("checkbox"), "hidden", 1),
	array("1C_CRM_CAT_XML_ID", GetMessage("CRM_CATALOG_XML_ID"), "", Array("text", 20), "visible", 1),
	array("selected_catalog_group_id", GetMessage("CRM_SELECTED_CATALOG_GROUP_ID"), $priceTypeId, Array("list", $arPriceType), "visible", 1),
	// export tab
	array("1CE_IBLOCK_ID", GetMessage("CAT_1CE_IBLOCK_ID"), $iblockId, Array("text", 50), "hidden", 2),
	array("1CE_ELEMENTS_PER_STEP", GetMessage("CAT_1CE_ELEMENTS_PER_STEP"), 100, Array("text", 5), "visible", 2),
	array("1CE_INTERVAL", GetMessage("CAT_1CE_INTERVAL"), "30", Array("text", 20), "visible", 2),
	array("1CE_GROUP_PERMISSIONS", GetMessage("CAT_1CE_GROUP_PERMISSIONS"), "-", Array("mlist", 5, $arUGroupsEx), "hidden", 2),
	array("1CE_USE_ZIP", GetMessage("CAT_1CE_USE_ZIP"), "Y", Array("checkbox"), "hidden", 2)
);

if($_SERVER['REQUEST_METHOD'] == "POST" && check_bitrix_sessid())
{
	for ($i = 0, $intCount = count($arAllOptions); $i < $intCount; $i++)
	{
		$tabNumber = $arAllOptions[$i][5];
		$name = $arAllOptions[$i][0];
		if (isset($arAllOptions[$i][4]) && $arAllOptions[$i][4] === "hidden")
			$val = $arAllOptions[$i][2];
		else
			$val = $_REQUEST[$tabNumber.'_'.$name];
		if($arAllOptions[$i][3][0]=="checkbox" && $val!="Y")
			$val = "N";
		if($arAllOptions[$i][3][0]=="mlist" && is_array($val))
		{
			$val = implode(",", $val);
		}
		if ($name === '1C_CRM_CAT_XML_ID')
		{
			$ib = new CIBlock();
			$ib->Update($iblockId, array('XML_ID' => $val));
			unset($ib);
		}
		else if ($name === 'selected_catalog_group_id')
		{
			COption::SetOptionInt("crm", $name, intval($val), $arAllOptions[$i][1]);
		}
		else
		{
			COption::SetOptionString("catalog", $name, $val, $arAllOptions[$i][1]);
		}
	}

	if ($this->request->isAjaxRequest())
	{
		$APPLICATION->RestartBuffer();
		$ajaxResponce["SUCCESS"] = "Y";
		echo \Bitrix\Main\Web\Json::encode($ajaxResponce);
		die();
	}

	if (!(isset($_REQUEST["IFRAME"]) && $_REQUEST["IFRAME"] === "Y"))
	{
		LocalRedirect(CComponentEngine::MakePathFromTemplate($arResult['PATH_TO_EXCH1C_INDEX'], array()));
	}
}

$tabNames = array(
	1 => "tab_catalog_import",
	2 => "tab_catalog_export"
);

foreach($arAllOptions as $Option)
{
	$tabNumber = $Option[5];
	if (isset($Option[4]) && $Option[4] === 'hidden')
		continue;

	if ($Option[0] === '1C_CRM_CAT_XML_ID')
	{
		$ib = new CIBlock();
		$arIb = $ib->GetByID($iblockId)->Fetch();
		$val = (is_array($arIb) && $arIb['XML_ID'] <> '') ? $arIb['XML_ID'] : '';
		unset($ib);
	}
	else
		$val = COption::GetOptionString("catalog", $Option[0], $Option[2]);
	$type = $Option[3];

	$fieldParams = array(
		'name' => $tabNumber.'_'.$Option[0],
		'title' => $Option[1],
		'type' => ($type[0] === 'mlist') ? 'list' : $type[0],
	);
	if ($type[0] === 'list' || $type[0] === 'mlist')
	{
		foreach ($type[1] as $itemValue => $itemName)
		$fieldParams["data"]['items'][] = array(
			"NAME" => $itemName,
			"VALUE" => $itemValue
		);
	}

	$arResult['FIELDS'][] = $fieldParams;
	$arResult['FIELDS_DATA'][$tabNumber.'_'.$Option[0]] = ($type[0] === 'mlist') ? explode(",", $val) : $val;
	$arResult['FIELDS_CONFIG'][$tabNumber-1]["elements"][] = array("name" => $tabNumber.'_'.$Option[0]);
}
// <-- Catalog import options
// </editor-fold>

$this->IncludeComponentTemplate();

$APPLICATION->AddChainItem(GetMessage('CRM_EXCH1C_LIST'), $arParams['PATH_TO_EXCH1C_INDEX']);
?>

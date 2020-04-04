<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

CPageOption::SetOptionString("main", "nav_page_in_session", "N");

if(!isset($arParams["CACHE_TIME"]))
	$arParams["CACHE_TIME"] = 3600;

$arParams["IBLOCK_TYPE"] = trim($arParams["IBLOCK_TYPE"]);
if(strlen($arParams["IBLOCK_TYPE"])<=0)
	$arParams["IBLOCK_TYPE"] = "library";
$arParams["IBLOCK_ID"] = trim($arParams["IBLOCK_ID"]);
$arParams["PARENT_SECTION"] = intval($arParams["PARENT_SECTION"]);
$arParams["INCLUDE_SUBSECTIONS"] = $arParams["INCLUDE_SUBSECTIONS"]!="N";

$arParams["SORT_BY1"] = trim($arParams["SORT_BY1"]);
if(strlen($arParams["SORT_BY1"])<=0)
	$arParams["SORT_BY1"] = "ACTIVE_FROM";
if($arParams["SORT_ORDER1"]!="ASC")
	$arParams["SORT_ORDER1"]="DESC";
if(strlen($arParams["SORT_BY2"])<=0)
	$arParams["SORT_BY2"] = "SORT";
if($arParams["SORT_ORDER2"]!="DESC")
	$arParams["SORT_ORDER2"]="ASC";

if(strlen($arParams["FILTER_NAME"])<=0 || !preg_match("^[A-Za-z_][A-Za-z01-9_]*$", $arParams["FILTER_NAME"], $arMatches))
{
	$arrFilter = array();
}
else
{
	$arrFilter = $GLOBALS[$arParams["FILTER_NAME"]];
	if(!is_array($arrFilter))
		$arrFilter = array();
}

$arParams["CHECK_DATES"] = $arParams["CHECK_DATES"]!="N";

if(!is_array($arParams["FIELD_CODE"]))
	$arParams["FIELD_CODE"] = array();
foreach($arParams["FIELD_CODE"] as $key=>$val)
	if(!$val)
		unset($arParams["FIELD_CODE"][$key]);

if(!is_array($arParams["PROPERTY_CODE"]))
	$arParams["PROPERTY_CODE"] = array();
foreach($arParams["PROPERTY_CODE"] as $key=>$val)
	if($val==="")
		unset($arParams["PROPERTY_CODE"][$key]);

$arParams["DETAIL_URL"]=trim($arParams["DETAIL_URL"]);

$arParams["DOCS_COUNT"] = intval($arParams["DOCS_COUNT"]);
if($arParams["DOCS_COUNT"]<=0)
	$arParams["DOCS_COUNT"] = 20;

$arParams["CACHE_FILTER"] = $arParams["CACHE_FILTER"]=="Y";
if(!$arParams["CACHE_FILTER"] && count($arrFilter)>0)
	$arParams["CACHE_TIME"] = 0;

$arParams["SET_TITLE"] = $arParams["SET_TITLE"]!="N";
$arParams["DISPLAY_PANEL"] = $arParams["DISPLAY_PANEL"]=="Y"; //Turn off by default
$arParams["ADD_SECTIONS_CHAIN"] = $arParams["ADD_SECTIONS_CHAIN"]!="N"; //Turn on by default
$arParams["INCLUDE_IBLOCK_INTO_CHAIN"] = $arParams["INCLUDE_IBLOCK_INTO_CHAIN"]!="N";
$arParams["ACTIVE_DATE_FORMAT"] = trim($arParams["ACTIVE_DATE_FORMAT"]);
if(strlen($arParams["ACTIVE_DATE_FORMAT"])<=0)
	$arParams["ACTIVE_DATE_FORMAT"] = $DB->DateFormatToPHP(CSite::GetDateFormat("SHORT"));
$arParams["PREVIEW_TRUNCATE_LEN"] = (intval($arParams["PREVIEW_TRUNCATE_LEN"]) > 0) ? intval($arParams["PREVIEW_TRUNCATE_LEN"]) : 255;
$arParams["HIDE_LINK_WHEN_NO_DETAIL"] = $arParams["HIDE_LINK_WHEN_NO_DETAIL"]=="Y";

$arParams["DISPLAY_TOP_PAGER"] = $arParams["DISPLAY_TOP_PAGER"]=="Y";
$arParams["DISPLAY_BOTTOM_PAGER"] = $arParams["DISPLAY_BOTTOM_PAGER"]!="N";
$arParams["PAGER_TITLE"] = trim($arParams["PAGER_TITLE"]);
$arParams["PAGER_SHOW_ALWAYS"] = $arParams["PAGER_SHOW_ALWAYS"]!="N";
$arParams["PAGER_TEMPLATE"] = trim($arParams["PAGER_TEMPLATE"]);
$arParams["PAGER_DESC_NUMBERING"] = $arParams["PAGER_DESC_NUMBERING"]=="Y";
$arParams["PAGER_DESC_NUMBERING_CACHE_TIME"] = intval($arParams["PAGER_DESC_NUMBERING_CACHE_TIME"]);

if($arParams["DISPLAY_TOP_PAGER"] || $arParams["DISPLAY_BOTTOM_PAGER"])
{
	$arNavParams = array("nPageSize"=>$arParams["DOCS_COUNT"], "bDescPageNumbering"=>$arParams["PAGER_DESC_NUMBERING"]);
	$arNavigation = CDBResult::GetNavParams($arNavParams);
	if($arNavigation["PAGEN"]==0 && $arParams["PAGER_DESC_NUMBERING_CACHE_TIME"]>0)
		$arParams["CACHE_TIME"] = $arParams["PAGER_DESC_NUMBERING_CACHE_TIME"];
}
else
{
	$arNavParams = array("nPageSize"=>$arParams["DOCS_COUNT"], "bDescPageNumbering"=>$arParams["PAGER_DESC_NUMBERING"]);
	$arNavigation = false;
}
$arParams["USE_PERMISSIONS"] = $arParams["USE_PERMISSIONS"]=="Y";
if(!is_array($arParams["GROUP_PERMISSIONS"]))
	$arParams["GROUP_PERMISSIONS"] = array(1);

$bUSER_HAVE_ACCESS = !$arParams["USE_PERMISSIONS"];
if($arParams["USE_PERMISSIONS"] && isset($GLOBALS["USER"]) && is_object($GLOBALS["USER"]))
{
	$arUserGroupArray = $GLOBALS["USER"]->GetUserGroupArray();
	foreach($arParams["GROUP_PERMISSIONS"] as $PERM)
	{
		if(in_array($PERM, $arUserGroupArray))
		{
			$bUSER_HAVE_ACCESS = true;
			break;
		}
	}
}

$arResult["ITEMS"] = array();

if($this->StartResultCache(false, array($USER->GetGroups(), $bUSER_HAVE_ACCESS, $arNavigation, $arrFilter)))
{
	if(!CModule::IncludeModule("iblock"))
	{
		$this->AbortResultCache();
		ShowError(GetMessage("IBLOCK_MODULE_NOT_INSTALLED"));
		return;
	}
	if(is_numeric($arParams["IBLOCK_ID"]))
	{
		$rsIBlock = CIBlock::GetList(array(), array("ACTIVE" => "Y", "ID" => $arParams["IBLOCK_ID"]));
	}
	else
	{
		$rsIBlock = CIBlock::GetList(array(), array("ACTIVE" => "Y", "CODE" => $arParams["IBLOCK_ID"]));
	}
	if($arResult = $rsIBlock->GetNext())
	{
		$arResult["USER_HAVE_ACCESS"] = $bUSER_HAVE_ACCESS;

		if(!CModule::IncludeModule("webdav"))
		{
			$this->AbortResultCache();
			ShowError(GetMessage("WEBDAV_MODULE_NOT_INSTALLED"));
			return;
		}

		$ob = new CWebDavIblock($arResult['ID'], '', array());
		$arFiles = array();

		$arOptions = array('path' => '/', 'depth' => '1', );
		$arFilter = array(true);
		$arPropParams = array('PARENT_ID' => null, 'return' => 'nav_result', 'COLUMNS' => array('ACTIVE_FROM', 'PREVIEW_PICTURE'));
		
		$bGetProperty = count($arParams["PROPERTY_CODE"])>0;
		if($bGetProperty)
			$arPropParams['COLUMNS'][]="PROPERTY_*";
		if($arParams["CHECK_DATES"])
			$arFilter["ACTIVE_DATE"] = "Y";

		if($arParams["PARENT_SECTION"]>0)
		{
			$arFilter["SECTION_ID"] = $arParams["PARENT_SECTION"];
			$arPropParams["PARENT_ID"] = $arParams["PARENT_SECTION"];
			if($arParams["INCLUDE_SUBSECTIONS"])
				$arFilter["INCLUDE_SUBSECTIONS"] = "Y";
		}

		$arPropParams['FILTER'] = $arFilter;

		global $by, $order;
		$by	= $arParams["SORT_BY1"];
		$order = $arParams["SORT_ORDER1"];

		if(isset($arParams["DOCS_COUNT_NEED_USE"]) && $arParams["DOCS_COUNT_NEED_USE"] == "Y")
		{
			$arPropParams["NAV_PARAMS"]["nTopCount"] = $arParams["DOCS_COUNT"];
		}
		$arrsElements = $ob->PROPFIND($arOptions, $arFiles, $arPropParams); 
		$rsElement = $arrsElements["NAV_RESULT"];
		$rsElement->NavStart($arNavParams["nPageSize"], false, $arNavigation["PAGEN"]);

		while ($arItem = $rsElement->Fetch())
		{
			$arItem["PREVIEW_TEXT"] = HTMLToTxt($arItem["PREVIEW_TEXT"]);
			if($arParams["PREVIEW_TRUNCATE_LEN"]>0)
			{
				$end_pos = $arParams["PREVIEW_TRUNCATE_LEN"];
				while(substr($arItem["PREVIEW_TEXT"],$end_pos,1)!=" " && $end_pos<strlen($arItem["PREVIEW_TEXT"]))
					$end_pos++;
				if($end_pos<strlen($arItem["PREVIEW_TEXT"]))
					$arItem["PREVIEW_TEXT"] = substr($arItem["PREVIEW_TEXT"], 0, $end_pos)."...";
			}

			if(strlen($arParams["DETAIL_URL"])>0)
			{
				$arItem["DETAIL_PAGE_URL"] = htmlspecialcharsbx(str_replace(
					array("#SERVER_NAME#", "#SITE_DIR#", "#IBLOCK_ID#", "#SECTION_ID#", "#ELEMENT_ID#"),
					array(SITE_SERVER_NAME, SITE_DIR, $arItem["IBLOCK_ID"], $arItem["IBLOCK_SECTION_ID"], $arItem["ID"]),
					$arParams["DETAIL_URL"]
				));
			}

			if(array_key_exists("PREVIEW_PICTURE", $arItem))
				$arItem["PREVIEW_PICTURE"] = CFile::GetFileArray($arItem["PREVIEW_PICTURE"]);
			if(array_key_exists("DETAIL_PICTURE", $arItem))
				$arItem["DETAIL_PICTURE"] = CFile::GetFileArray($arItem["DETAIL_PICTURE"]);

			$arItem["FILE_EXTENTION"] = htmlspecialcharsbx(strtolower(strrchr($arItem['~NAME'] , '.')));
			$arItem["FIELDS"] = array();
			foreach($arParams["FIELD_CODE"] as $code)
				if(array_key_exists($code, $arItem))
					$arItem["FIELDS"][$code] = $arItem[$code];

			$arItem["FIELDS"]["TIMESTAMP_X"] = CIBlockFormatProperties::DateFormat($arParams["ACTIVE_DATE_FORMAT"], MakeTimeStamp($arItem["TIMESTAMP_X"], CSite::GetDateFormat()));
			$arItem["DISPLAY_PROPERTIES"]=array();
			if($bGetProperty)
			{
				$dbProps = CIBlockElement::GetProperty($arItem['IBLOCK_ID'], $arItem['ID'], array("sort" => "asc"));
				if ($dbProps)
				{
					while($arProps = $dbProps->Fetch())
					{
						$arItem["PROPERTIES"][$arProps["CODE"]] = $arProps;
					}
				}
				foreach($arParams["PROPERTY_CODE"] as $pid)
				{
					$prop = &$arItem["PROPERTIES"][$pid];
					if((is_array($prop["VALUE"]) && count($prop["VALUE"])>0) ||
						(!is_array($prop["VALUE"]) && strlen($prop["VALUE"])>0))
					{
						$arItem["DISPLAY_PROPERTIES"][$pid] = CIBlockFormatProperties::GetDisplayValue($arItem, $prop, "news_out");
					}
				}
			}
			$arResult["ITEMS"][]=$arItem;
		}


		$arResult["NAV_STRING"] = $rsElement->GetPageNavStringEx($navComponentObject, $arParams["PAGER_TITLE"], $arParams["PAGER_TEMPLATE"], $arParams["PAGER_SHOW_ALWAYS"]);
		$arResult["NAV_CACHED_DATA"] = $navComponentObject->GetTemplateCachedData();
		$arResult["NAV_RESULT"] = $rsElement;
		$this->SetResultCacheKeys(array(
			"ID",
			"IBLOCK_TYPE_ID",
			"NAV_CACHED_DATA",
			"NAME",
			"SECTION",
		));

		$this->IncludeComponentTemplate();
	}
	else
	{
		$this->AbortResultCache();
		ShowError(GetMessage("T_DOCS_DOCS_NA"));
		@define("ERROR_404", "Y");
	}
}

if(isset($arResult["ID"]))
{
	if($USER->IsAuthorized())
	{
		if($GLOBALS["APPLICATION"]->GetShowIncludeAreas() && CModule::IncludeModule("iblock"))
			$this->AddIncludeAreaIcons(CIBlock::ShowPanel($arResult["ID"], 0, 0, $arResult["IBLOCK_TYPE_ID"], true));
	}

	$this->SetTemplateCachedData($arResult["NAV_CACHED_DATA"]);

	if($arParams["SET_TITLE"])
	{
		$APPLICATION->SetTitle($arResult["NAME"]);
	}

	if($arParams["INCLUDE_IBLOCK_INTO_CHAIN"] && isset($arResult["NAME"]))
	{
		$APPLICATION->AddChainItem($arResult["NAME"]);
	}

	if($arParams["ADD_SECTIONS_CHAIN"] && is_array($arResult["SECTION"]))
	{
		foreach($arResult["SECTION"]["PATH"] as $arPath)
		{
			$APPLICATION->AddChainItem($arPath["NAME"], $arPath["SECTION_PAGE_URL"]);
		}
	}
}

?>

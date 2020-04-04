<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arGadgetParams["LIST_URL"] = ($arGadgetParams["LIST_URL"]?$arGadgetParams["LIST_URL"]:"/about/life.php");
$arGadgetParams["ACTIVE_DATE_FORMAT"] = ($arGadgetParams["ACTIVE_DATE_FORMAT"] ? $arGadgetParams["ACTIVE_DATE_FORMAT"] : $arParams["DATE_FORMAT"]);

?>
<?$APPLICATION->IncludeComponent(
	"bitrix:news.list",
	"table",
	Array(
		"IBLOCK_TYPE"	=>	$arGadgetParams["IBLOCK_TYPE"],
		"IBLOCK_ID"	=>	$arGadgetParams["IBLOCK_ID"],
		"NEWS_COUNT"	=>	(isset($arGadgetParams["NEWS_COUNT"])?$arGadgetParams["NEWS_COUNT"]:5),
		"SORT_BY1" => "ACTIVE_FROM",
		"SORT_ORDER1" => "DESC",
		"SORT_BY2" => "ID",
		"SORT_ORDER2" => "DESC",
		"FILTER_NAME" => "",
		"FIELD_CODE" => array(0=>"",1=>"",2=>"",),
		"PROPERTY_CODE" => array(0=>"",1=>"",2=>"",),
		"DETAIL_URL" => $arGadgetParams["DETAIL_URL"],
		"AJAX_MODE" => "N",
		"AJAX_OPTION_SHADOW" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "N",
		"AJAX_OPTION_HISTORY" => "N",
		"CACHE_TYPE" => $arGadgetParams["CACHE_TYPE"],
		"CACHE_TIME" => $arGadgetParams["CACHE_TIME"],
		"CACHE_FILTER" => "N",
		"PREVIEW_TRUNCATE_LEN" => "",
		"ACTIVE_DATE_FORMAT" => $arGadgetParams["ACTIVE_DATE_FORMAT"],
		"DISPLAY_PANEL" => "N",
		"SET_TITLE" => "N",
		"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
		"ADD_SECTIONS_CHAIN" => "N",
		"HIDE_LINK_WHEN_NO_DETAIL" => "N",
		"PARENT_SECTION" => "",
		"DISPLAY_TOP_PAGER" => "N",
		"DISPLAY_BOTTOM_PAGER" => "N",
		"PAGER_TITLE" => "",
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_TEMPLATE" => "",
		"PAGER_DESC_NUMBERING" => "N",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"DISPLAY_DATE"	=>	$arGadgetParams["DISPLAY_DATE"],
		"DISPLAY_NAME"	=>	"Y",
		"DISPLAY_PICTURE"	=>	$arGadgetParams["DISPLAY_PICTURE"],
		"DISPLAY_PREVIEW_TEXT"	=>	$arGadgetParams["DISPLAY_PREVIEW_TEXT"],
		"INTRANET_TOOLBAR" => "N",
		"bxpiwidth" => "693"
	),
	false,
	Array("HIDE_ICONS"=>"Y")
);?>

<?if(strlen($arGadgetParams["LIST_URL"])>0):?>
<br />
<div align="right"><a href="<?=htmlspecialcharsbx($arGadgetParams["LIST_URL"])?>"><?echo GetMessage("GD_LIFE_ALL")?></a> <a href="<?=htmlspecialcharsbx($arGadgetParams["LIST_URL"])?>"><img width="7" height="7" border="0" src="/images/icons/arrows.gif" /></a>
<br /></div>
<?endif?>

<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arGadgetParams["LIST_URL"] = ($arGadgetParams["LIST_URL"]?$arGadgetParams["LIST_URL"]:"/about/media.php");
?>
<?$APPLICATION->IncludeComponent(
	"bitrix:iblock.tv",
	".default",
	Array(
		"IBLOCK_TYPE"	=>	$arGadgetParams["IBLOCK_TYPE"],
		"IBLOCK_ID"	=>	$arGadgetParams["IBLOCK_ID"],

		"PATH_TO_FILE" => $arGadgetParams["PATH_TO_FILE"],
		"DURATION" => $arGadgetParams["DURATION"],
		"SECTION_ID" => $arGadgetParams["SECTION_ID"],
		"ELEMENT_ID" => $arGadgetParams["ELEMENT_ID"],
		"WIDTH" => $arGadgetParams["WIDTH"], 		//"400",
		"HEIGHT" => $arGadgetParams["HEIGHT"], 	//"300",

		"CACHE_TYPE" => $arGadgetParams["CACHE_TYPE"],
		"CACHE_TIME" => $arGadgetParams["CACHE_TIME"],

		"INTRANET_TOOLBAR" => "N",
		"DISPLAY_PANEL" => "N"
	),
	false,
	Array("HIDE_ICONS"=>"Y")
);?>

<?if(strlen($arGadgetParams["LIST_URL"])>0):?>
<br />
<div align="right"><a href="<?=htmlspecialcharsbx($arGadgetParams["LIST_URL"])?>"><?echo GetMessage("GD_VIDEO_MORE")?></a> <a href="<?=htmlspecialcharsbx($arGadgetParams["LIST_URL"])?>"><img width="7" height="7" border="0" src="/images/icons/arrows.gif" /></a>
<br /></div>
<?endif?>

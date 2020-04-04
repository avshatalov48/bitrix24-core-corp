<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arGadgetParams["LIST_URL"] = ($arGadgetParams["LIST_URL"] ? $arGadgetParams["LIST_URL"] : "/services/votes.php");
?>
<?$APPLICATION->IncludeComponent(
	"bitrix:voting.current",
	"main_page",
	Array(
		"CHANNEL_SID" => $arGadgetParams["CHANNEL_SID"],
		"AJAX_MODE" => "N",
		"AJAX_OPTION_SHADOW" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "N",
		"AJAX_OPTION_HISTORY" => "N",
		"CACHE_TYPE" => $arGadgetParams["CACHE_TYPE"],
		"CACHE_TIME" => $arGadgetParams["CACHE_TIME"]
	),
	false,
	Array("HIDE_ICONS"=>"Y")
);?>
<?if(strlen($arGadgetParams["LIST_URL"])>0):?>
<br />
<a href="<?=htmlspecialcharsbx($arGadgetParams["LIST_URL"])?>"><?echo GetMessage("GD_VOTE_MORE")?></a> <a href="<?=htmlspecialcharsbx($arGadgetParams["LIST_URL"])?>"><img width="7" height="7" border="0" src="/images/icons/arrows.gif" /></a>
<br />
<?endif?>

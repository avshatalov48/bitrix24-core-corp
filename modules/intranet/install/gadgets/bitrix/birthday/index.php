<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arGadgetParams["NUM_USERS"] = intval($arGadgetParams["NUM_USERS"]);
$arGadgetParams["NUM_USERS"] = ($arGadgetParams["NUM_USERS"]>0 && $arGadgetParams["NUM_USERS"]<=50 ? $arGadgetParams["NUM_USERS"] : 5);

$arGadgetParams["LIST_URL"] = ($arGadgetParams["LIST_URL"]?$arGadgetParams["LIST_URL"]:"/company/birthdays.php");
?>
<?$APPLICATION->IncludeComponent(
	"bitrix:intranet.structure.birthday.nearest",
	"include_area",
	Array(
		"STRUCTURE_PAGE" => $arGadgetParams["STRUCTURE_PAGE"],
		"DETAIL_URL" => $arGadgetParams["DETAIL_URL"],
		"PM_URL" => $arGadgetParams["PM_URL"],
		"PATH_TO_CONPANY_DEPARTMENT" => $arParams["PATH_TO_CONPANY_DEPARTMENT"],
		"PATH_TO_VIDEO_CALL" => $arParams["PATH_TO_VIDEO_CALL"],
		"STRUCTURE_FILTER" => "structure",
		"NUM_USERS" => $arGadgetParams["NUM_USERS"],
		"AJAX_MODE" => "N",
		"AJAX_OPTION_SHADOW" => "Y",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "N",
		"AJAX_OPTION_HISTORY" => "N",
		"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
		"SHOW_YEAR" => $arGadgetParams["SHOW_YEAR"],
		"USER_PROPERTY" => $arGadgetParams["USER_PROPERTY"],
		"DEPARTMENT" => $arGadgetParams["DEPARTMENT"],
		"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
		"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
		"DATE_FORMAT" => $arParams["DATE_FORMAT"],
		"DATE_FORMAT_NO_YEAR" => $arParams["DATE_FORMAT_NO_YEAR"],
	),
	false,
	Array("HIDE_ICONS"=>"Y")
);?>
<?if(strlen($arGadgetParams["LIST_URL"])>0):?>
<br />
<a href="<?=htmlspecialcharsbx($arGadgetParams["LIST_URL"])?>"><?echo GetMessage("GD_BIRTHDAY_LINK")?></a> <a href="<?=htmlspecialcharsbx($arGadgetParams["LIST_URL"])?>"><img width="7" height="7" border="0" src="/images/icons/arrows.gif" /></a>
<br />
<?endif?>
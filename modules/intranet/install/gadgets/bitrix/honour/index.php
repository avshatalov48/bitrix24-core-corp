<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arGadgetParams["LIST_URL"] = ($arGadgetParams["LIST_URL"] ? $arGadgetParams["LIST_URL"] : "/company/leaders.php");

$arGadgetParams["NUM_USERS"] = intval($arGadgetParams["NUM_USERS"]);
$arGadgetParams["NUM_USERS"] = ($arGadgetParams["NUM_USERS"]>0 && $arGadgetParams["NUM_USERS"]<50 ? $arGadgetParams["NUM_USERS"] : 5);
?>
<?$APPLICATION->IncludeComponent(
	"bitrix:intranet.structure.honour",
	"include_area",
	Array(
		"NUM_USERS" => $arGadgetParams["NUM_USERS"],
		"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
		"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
		"PM_URL"	=>	$arParams["PM_URL"],
		"PATH_TO_CONPANY_DEPARTMENT" => $arParams["PATH_TO_CONPANY_DEPARTMENT"],
		"PATH_TO_VIDEO_CALL" => $arParams["PATH_TO_VIDEO_CALL"],
		"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
		"SHOW_YEAR" => $arParams["SHOW_YEAR"],
	),
	false,
	Array("HIDE_ICONS"=>"Y")
);?>

<?if(strlen($arGadgetParams["LIST_URL"])>0):?>
<br />
<a href="<?=htmlspecialcharsbx($arGadgetParams["LIST_URL"])?>"><?=GetMessage("GD_HONOUR_DETAIL")?></a> <a href="<?=htmlspecialcharsbx($arGadgetParams["LIST_URL"])?>"><img width="7" height="7" border="0" src="/images/icons/arrows.gif" /></a>
<br />
<?endif?>

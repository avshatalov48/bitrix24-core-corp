<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arGadgetParams["LIST_URL"] = (
	isset($arGadgetParams["LIST_URL"])
		? $arGadgetParams["LIST_URL"] 
		: SITE_DIR."company/"
);
?>
<?$APPLICATION->IncludeComponent("bitrix:intranet.structure.selector", "include_area", Array(
	"LIST_URL"	=>	$arGadgetParams["LIST_URL"],
	"FILTER_NAME"	=>	"company_search",
	"FILTER_DEPARTMENT_SINGLE"	=>	"Y",
	"FILTER_SESSION"	=>	"N"
	),
	false,
	Array("HIDE_ICONS"=>"Y")
);?>

<?if(strlen($arGadgetParams["LIST_URL"])>0):?>
<br />
<div align="right"><a href="<?=htmlspecialcharsbx($arGadgetParams["LIST_URL"])?>"><?echo GetMessage("GD_SEARCH_EMPLOYEE_MORE")?></a> <a href="<?=htmlspecialcharsbx($arGadgetParams["LIST_URL"])?>"><img width="7" height="7" border="0" src="/images/icons/arrows.gif" /></a>
<br /></div>
<?endif?>

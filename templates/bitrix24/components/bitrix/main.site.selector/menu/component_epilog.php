<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$arMenu = Array();
foreach ($arResult["SITES"] as $key => $arSite)
{
	$arMenu[] = Array(
			$arSite["NAME"], 
			$arSite["DIR"],
			Array(), 
			Array(), 
			"" 
		);
}
$GLOBALS["arMenuSites"] = $arMenu;
?>
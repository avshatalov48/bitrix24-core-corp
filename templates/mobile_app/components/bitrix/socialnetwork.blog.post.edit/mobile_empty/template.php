<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (
	$arResult["ERROR_MESSAGE"] <> ''
	|| $arResult["FATAL_MESSAGE"] <> ''
)
{
	$GLOBALS["APPLICATION"]->RestartBuffer();
	echo CUtil::PhpToJSObject(array(
		"error" => ($arResult["FATAL_MESSAGE"] <> '' ? $arResult["FATAL_MESSAGE"] : $arResult["ERROR_MESSAGE"])
	));
	die();
}
?>
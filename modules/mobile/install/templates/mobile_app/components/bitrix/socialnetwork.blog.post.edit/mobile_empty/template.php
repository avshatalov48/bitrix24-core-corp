<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (
	strlen($arResult["ERROR_MESSAGE"]) > 0
	|| strlen($arResult["FATAL_MESSAGE"]) > 0
)
{
	$GLOBALS["APPLICATION"]->RestartBuffer();
	echo CUtil::PhpToJSObject(array(
		"error" => (strlen($arResult["FATAL_MESSAGE"]) > 0 ? $arResult["FATAL_MESSAGE"] : $arResult["ERROR_MESSAGE"])
	));
	die();
}
?>
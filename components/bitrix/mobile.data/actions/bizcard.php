<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/**
 * @var CUser $USER
 * @var CMain $APPLICATION
 */

if(!IsModuleInstalled("bizcard"))
{
	$APPLICATION->RestartBuffer();
	\Bitrix\Main\Web\Json::encode(array(
		"STATUS"=>"failed",
		"ERROR"=>"Module \"bizcard\" is not installed"
	));
}
else
{
	include(\Bitrix\Main\Application::getDocumentRoot()."/bitrix/tools/bizcard/bizcard.php");
}

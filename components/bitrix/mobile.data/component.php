<? use Bitrix\Main\Context;
use Bitrix\Mobile\Auth;

if (!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var $APPLICATION CMain
 * @var $USER CUser
 * @var $arParams array
 */
Bitrix\Main\Loader::includeModule("mobileapp");
Bitrix\Main\Loader::includeModule("mobile");
Bitrix\MobileApp\Mobile::getInstance();

include_once(__DIR__ . "/functions.php");

defineApiVersion();

$isSessidValid = true;
if(array_key_exists("sessid", $_REQUEST) && $_REQUEST["sessid"] <> '')
{
	$isSessidValid = check_bitrix_sessid();
}

$mobileAction = $_REQUEST["mobile_action"] ?? null;

if ($USER->IsAuthorized() && $isSessidValid)
{
	$isBackground = Context::getCurrent()->getServer()->get("HTTP_BX_MOBILE_BACKGROUND");
	if($isBackground != "true" && $mobileAction != "checkout")
    {
        Bitrix\Mobile\User::setOnline();
    }
}

if ($mobileAction)//Executing some action
{
	$APPLICATION->RestartBuffer();
	$action = $mobileAction;
	$actionList = new Bitrix\Mobile\Action();
	$actionList->executeAction($action, $arParams);

	CMain::FinalActions();
	die();
}
elseif (!empty($_REQUEST["captcha_sid"]))//getting captcha image
{
	$APPLICATION->RestartBuffer();
	$actionList = new Bitrix\Mobile\Action();
	$actionList->executeAction("get_captcha", $arParams);
	die();
}
elseif (!empty($_REQUEST["manifest_id"]))//getting content of appcache manifest
{
	include($_SERVER["DOCUMENT_ROOT"] .\Bitrix\Main\Data\AppCacheManifest::MANIFEST_CHECK_FILE);
	die();
}
elseif(!$USER->IsAuthorized() || !$isSessidValid)
{
	$APPLICATION->RestartBuffer();
	Auth::setNotAuthorizedHeaders();
	echo json_encode(Auth::getNotAuthorizedResponse());
	die();
}

$this->IncludeComponentTemplate();
?>

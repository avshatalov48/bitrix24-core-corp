<?
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);
define("NO_AGENT_CHECK", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

if (!CModule::IncludeModule("voximplant"))
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'VI_MODULE_NOT_INSTALLED'));
	CMain::FinalActions();
	die();
}

if(!check_bitrix_sessid())
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'SESSION_ERROR'));
	CMain::FinalActions();
	die();
}

$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
if(!$permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_LINE, \Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY))
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'AUTHORIZE_ERROR'));
	CMain::FinalActions();
	die();
}

if ($_POST['VI_PHONE_ORDER'] == 'Y')
{
	CUtil::decodeURIComponent($_POST);

	$arSend = Array(
		'NAME' => $_POST['FORM_NAME'],
		'CONTACT' => $_POST['FORM_CONTACT'],
		'REG_CODE' => $_POST['FORM_REG_CODE'],
		'PHONE' => $_POST['FORM_PHONE'],
		'EMAIL' => $_POST['FORM_EMAIL'],
	);
	CVoxImplantPhoneOrder::Send($arSend);

	$arSend = Array('ERROR' => '');

	echo CUtil::PhpToJsObject($arSend);
}
else if ($_POST['VI_PHONE_ORDER_EXTRA'] == 'Y')
{
	CUtil::decodeURIComponent($_POST);

	$arSend = Array(
		'TYPE' => $_POST['FORM_TYPE'],
	);
	CVoxImplantPhoneOrder::RequestService($arSend);

	$arSend = Array('ERROR' => '');

	echo CUtil::PhpToJsObject($arSend);
}
else
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'UNKNOWN_ERROR'));
}

CMain::FinalActions();
die();
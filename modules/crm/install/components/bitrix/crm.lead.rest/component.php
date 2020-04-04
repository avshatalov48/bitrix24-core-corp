<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule('crm'))
	return;

global $APPLICATION, $DB, $USER;

Header('X-CRM-Version: 11.5.0'); // just to check version. we can manually enlarge it in further updates
Header('Content-Type: application/json; charset=utf-8');

// check data
$arData = $_SERVER['REQUEST_METHOD'] == 'POST' ? $_POST : $_GET;
$arData = $APPLICATION->ConvertCharsetArray($arData, 'UTF-8', LANG_CHARSET);

if(is_array($_FILES))
{
	foreach($_FILES as $k => $v)
	{
		if(CCrmFileProxy::IsUploadedFile($v))
		{
			$arData[$k] = $v;
		}
	}
}

if (empty($arData))
{
	$APPLICATION->RestartBuffer();
	echo CUtil::PhpToJSObject(array('error' => 400, 'error_message' => $APPLICATION->ConvertCharset(GetMessage('CRM_REST_ERROR_BAD_REQUEST'), LANG_CHARSET, 'UTF-8')));
	exit();
}

$bCheckPerms = true;

// authorize
if (isset($arData['LOGIN']) && isset($arData['PASSWORD']))
{
	$CCrmUser = new CUser();
	if (($error = $CCrmUser->Login($arData['LOGIN'], $arData['PASSWORD'], 'N', 'Y')) !== true)
	{
		$APPLICATION->RestartBuffer();
		echo CUtil::PhpToJSObject(array('error' => 403, 'error_message' => $error === false ? "OTP required" : $APPLICATION->ConvertCharset(strip_tags(nl2br($error['MESSAGE'])), LANG_CHARSET, 'UTF-8')));
		exit();
	}

	CCRMLeadRest::CreateAuthHash($arData);
}
elseif ($arData['AUTH'])
{
	if (!CCRMLeadRest::CheckAuthHash($arData))
	{
		$APPLICATION->RestartBuffer();
		echo CUtil::PhpToJSObject(array('error' => 403, 'error_message' => $APPLICATION->ConvertCharset(GetMessage('CRM_PERMISSION_DENIED'), LANG_CHARSET, 'UTF-8')));
		exit();
	}
}
else
{
	$APPLICATION->RestartBuffer();
	echo CUtil::PhpToJSObject(array('error' => 403, 'error_message' => $APPLICATION->ConvertCharset(GetMessage('CRM_REST_ERROR_BAD_AUTH'), LANG_CHARSET, 'UTF-8')));
	exit();
}

// check access
$CCrmLead = new CCrmLead();
if ($CCrmLead->cPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'ADD'))
{
	$APPLICATION->RestartBuffer();
	echo CUtil::PhpToJSObject(array('error' => 403, 'error_message' => $APPLICATION->ConvertCharset(GetMessage('CRM_PERMISSION_DENIED'), LANG_CHARSET, 'UTF-8')));
	exit();
}

$METHOD = $arData['method'];
if (strlen($METHOD) <= 0)
{
	$METHOD = 'lead.add';
}

switch ($METHOD)
{
	case 'lead.add':
		$response = CCRMLeadRest::AddLead($arData, $CCrmLead);
		$APPLICATION->RestartBuffer();
		echo $response;
	break;
	case 'lead.add.bundle':
		$response = CCRMLeadRest::AddLeadBundle($arData['LEADS'], $CCrmLead);
		$APPLICATION->RestartBuffer();
		echo $response;
	break;
	case 'lead.get_fields':
		$response = CCRMLeadRest::GetFields();
		$APPLICATION->RestartBuffer();
		echo $response;
	break;
	default:
		$APPLICATION->RestartBuffer();
		echo CUtil::PhpToJSObject(array('error' => 400, 'error_message' => $APPLICATION->ConvertCharset(GetMessage('CRM_REST_ERROR_BAD_REQUEST'), LANG_CHARSET, 'UTF-8')));
}

CMain::FinalActions();
exit();
?>
<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define("NOT_CHECK_PERMISSIONS", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("DisableEventsCheck", true);

$siteId = '';
if (isset($_REQUEST['site_id']) && is_string($_REQUEST['site_id']))
	$siteId = substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site_id']), 0, 2);

if (!$siteId)
	define('SITE_ID', $siteId);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
{
	return;
}
/** @var \CMain $APPLICATION */

$currentUser = CCrmSecurityHelper::GetCurrentUser();
if (!$currentUser->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	return;
}

$CrmPerms = new CCrmPerms($currentUser->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	return;
}

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
$sendResponse = function($result)
{
	$GLOBALS['APPLICATION']->RestartBuffer();
	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	if(!empty($result))
	{
		echo CUtil::PhpToJSObject($result);
	}
	if(!defined('PUBLIC_AJAX_MODE'))
	{
		define('PUBLIC_AJAX_MODE', true);
	}
	require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
	die();
};

CUtil::JSPostUnescape();

if (!isset($_REQUEST['provider_id']))
{
	return;
}

$action = isset($_POST['action']) ? (string)$_POST['action'] : '';
/** @var \Bitrix\Crm\Integration\Sms\Provider\BaseInternal $provider */
$provider = \Bitrix\Crm\Integration\Sms\Manager::getProviderById($_REQUEST['provider_id']);

if (!$provider)
{
	return;
}

if ($action === 'registration')
{
	$registerResult = $provider->register($_POST);
	$sendResponse(array(
		'success' => $registerResult->isSuccess(),
		'errors' => $registerResult->getErrorMessages()
	));
}
else if ($action === 'confirmation' && !$provider->isConfirmed())
{
	$confirmResult = $provider->confirmRegistration(array(
		'confirm' => $_POST['confirm']
	));

	$sendResponse(array(
		'success' => $confirmResult->isSuccess(),
		'errors' => $confirmResult->getErrorMessages()
	));
}
else if ($action === 'send_message' && $provider->canUse())
{
	$ownerInfo = $provider->getOwnerInfo();
	$message = \Bitrix\Crm\Integration\Sms\Manager::createMessage(array(
		'to' => $ownerInfo['phone'],
		'text' => $_POST['text']
	), $provider);

	$sendResult = $message->send();

	$sendResponse(array(
		'success' => $sendResult->isSuccess(),
		'errors' => $sendResult->getErrorMessages()
	));
}
else if ($action === 'send_confirmation')
{
	$sendConfirmationResult = $provider->sendConfirmationCode();
	$sendResponse(array(
		'success' => $sendConfirmationResult->isSuccess(),
		'errors' => $sendConfirmationResult->getErrorMessages()
	));
}
else if ($action === 'disable_demo')
{
	if (count($provider->getSenderList()) > 1)
	{
		$provider->disableDemo();
		$sendResponse(array(
			'success' => true
		));
	}
	$sendResponse(array(
		'success' => false,
		'errors' => array(\Bitrix\Main\Localization\Loc::getMessage('CRM_CONFIG_SMS_DISABLE_DEMO_ERROR'))
	));
}
else if ($action === 'clear_options')
{
	$provider->clearOptions();
	$sendResponse(array(
		'success' => true
	));
}
$sendResponse(array('errors'=> array('Unknown action.')));
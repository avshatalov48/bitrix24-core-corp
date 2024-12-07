<?php
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_LANG_FILES', true);
define('DisableEventsCheck', true);
define('BX_STATISTIC_BUFFER_USED', false);
define('BX_PUBLIC_TOOLS', true);
define('PUBLIC_AJAX_MODE', true);

if (isset($_REQUEST['site_id']) && is_string($_REQUEST['site_id']))
{
	$siteID = $_REQUEST['site_id'];
	//Prevent LFI in prolog_before.php
	if($siteID !== '' && preg_match('/^[a-z0-9_]{2}$/i', $siteID) === 1)
	{
		define('SITE_ID', $siteID);
	}
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/bx_root.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if (!defined('LANGUAGE_ID') )
{
	$dbSite = CSite::GetByID(SITE_ID);
	$arSite = $dbSite ? $dbSite->Fetch() : null;
	define('LANGUAGE_ID', $arSite ? $arSite['LANGUAGE_ID'] : 'en');
}

//session_write_close();

if (!CModule::IncludeModule('crm'))
{
	die();
}

global $APPLICATION, $DB;
$curUser = CCrmSecurityHelper::GetCurrentUser();
if (!$curUser || !$curUser->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	die();
}

//$langID = isset($_REQUEST['lang_id'])? $_REQUEST['lang_id']: LANGUAGE_ID;
//__IncludeLang(dirname(__FILE__).'/lang/'.$langID.'/'.basename(__FILE__));

if(!function_exists('__CrmMobileConfigUserEmailEndResponse'))
{
	function __CrmMobileConfigUserEmailEndResponse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

$curUserPrems = CCrmPerms::GetCurrentUserPermissions();
$action = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : '';
if($action === 'SAVE_CONFIGURATION')
{
	__IncludeLang(__DIR__.'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$emailAddresser = isset($_REQUEST['EMAIL_ADDRESSER']) ? $_REQUEST['EMAIL_ADDRESSER'] : '';

	if($emailAddresser === '')
	{
		__CrmMobileConfigUserEmailEndResponse(array('ERROR' => GetMessage('M_CRM_CONFIG_USER_EMAIL_EMPTY')));
	}

	if(!check_email($emailAddresser))
	{
		__CrmMobileConfigUserEmailEndResponse(array('ERROR' => GetMessage('M_CRM_CONFIG_USER_EMAIL_INVALID')));
	}

	CUserOptions::SetOption('crm', 'activity_email_addresser', $emailAddresser);
	$addresser = CCrmMailHelper::ParseEmail($emailAddresser);
	__CrmMobileConfigUserEmailEndResponse(
		array(
			'SAVED_EMAIL_ADDRESSER' => $addresser['ORIGINAL'],
			'SAVED_EMAIL_ADDRESSER_NAME' => $addresser['NAME'],
			'SAVED_EMAIL_ADDRESSER_EMAIL' => $addresser['EMAIL']
		)
	);
}
else
{
	__CrmMobileConfigUserEmailEndResponse(array('ERROR' => 'Action is not supported in current context.'));
}





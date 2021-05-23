<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
{
	return;
}
/*
 * ONLY 'POST' SUPPORTED
 * SUPPORTED MODES:
 * 'SAVE' - add/update category fields
 */
global $APPLICATION;

$user = CCrmSecurityHelper::GetCurrentUser();
if (!$user->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	return;
}

$userPermissions = CCrmPerms::GetCurrentUserPermissions();

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
if(!function_exists('__CrmActivityCustomTypeEndJsonResonse'))
{
	function __CrmActivityCustomTypeEndJsonResonse($result)
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
	}
}

CUtil::JSPostUnescape();
$mode = isset($_POST['MODE']) ? $_POST['MODE'] : '';
if($mode === '' && isset($_POST['ACTION']))
{
	$mode = $_POST['ACTION'];
}
if($mode === '')
{
	__CrmActivityCustomTypeEndJsonResonse(array('ERROR' => 'MODE IS NOT DEFINED!'));
}

if($mode === 'SAVE')
{
	if(!\CCrmAuthorizationHelper::CheckConfigurationUpdatePermission($userPermissions))
	{
		__CrmActivityCustomTypeEndJsonResonse(array('ERROR' => 'ACCESS DENIED!'));
	}

	$itemID = isset($_POST['ITEM_ID']) ? (int)$_POST['ITEM_ID'] : 0;
	$fields = isset($_POST['FIELDS']) && is_array($_POST['FIELDS']) ? $_POST['FIELDS'] : array();
	if(empty($fields))
	{
		__CrmActivityCustomTypeEndJsonResonse(array('ERROR' => 'FIELDS ARE NOT FOUND!'));
	}

	if($itemID > 0)
	{
		try
		{
			Bitrix\Crm\Activity\CustomType::update($itemID, $fields);
		}
		catch(Bitrix\Crm\Entry\UpdateException $ex)
		{
			__CrmActivityCustomTypeEndJsonResonse(array('ERROR' => $ex->getLocalizedMessage()));
		}
	}
	else
	{
		try
		{
			$itemID = Bitrix\Crm\Activity\CustomType::add($fields);
		}
		catch(Bitrix\Crm\Entry\AddException $ex)
		{
			__CrmActivityCustomTypeEndJsonResonse(array('ERROR' => $ex->getLocalizedMessage()));
		}
	}

	__CrmActivityCustomTypeEndJsonResonse(array('DATA' => array('ITEM_ID' => $itemID)));
}

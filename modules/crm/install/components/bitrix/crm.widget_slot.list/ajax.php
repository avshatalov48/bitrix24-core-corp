<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
{
	return;
}

if(!function_exists('__ajaxEndJsonResonse'))
{
	function __ajaxEndJsonResonse($result)
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

$currentUser = CCrmSecurityHelper::GetCurrentUser();
if (!$currentUser || !$currentUser->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	__ajaxEndJsonResonse(array('ERROR' => 'Access denied.'));
}

$currentUserPermissions = CCrmPerms::GetCurrentUserPermissions();
if (!CCrmAuthorizationHelper::CheckConfigurationUpdatePermission($currentUserPermissions))
{
	__ajaxEndJsonResonse(array('ERROR' => 'Access denied.'));
}

CUtil::JSPostUnescape();
$action = isset($_POST['ACTION']) ? $_POST['ACTION'] : '';
if(strlen($action) == 0)
{
	__ajaxEndJsonResonse(array('ERROR' => 'Invalid request. The "Action" parameter is not found.'));
}

if($action == 'SAVE_BINDINGS')
{
	$params = isset($_POST['PARAMS'])  ? $_POST['PARAMS'] : null;
	$ID = isset($params['ID'])  ? $params['ID'] : '';
	if($ID === '')
	{
		__ajaxEndJsonResonse(array('ERROR' => 'Invalid request. The "ID" parameter is not found.'));
	}
	$ID = strtoupper($ID);

	$bindings = isset($params['BINDINGS']) ? $params['BINDINGS'] : array();
	if(!is_array($bindings))
	{
		__ajaxEndJsonResonse(array('ERROR' => 'Invalid request. The "BINDINGS" parameter is not found or has invalid type.'));
	}

	$bindingMap = Bitrix\Crm\Statistics\StatisticFieldBindingMap::createFromArray($bindings);
	if($bindingMap->getCount() > Bitrix\Crm\Statistics\StatisticEntryManager::getSlotLimit())
	{
		__ajaxEndJsonResonse(array('ERROR' => 'Slot limit is exceeded.'));
	}

	Bitrix\Crm\Statistics\StatisticEntryManager::setSlotBindingMap($ID, $bindingMap);
	__ajaxEndJsonResonse(array('RESULT' => array('ID' => $ID, 'BINDINGS' => $bindingMap->toArray())));
}
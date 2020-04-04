<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2014 Bitrix
 */

/**
 * Bitrix vars
 *
 * @global CUser $USER
 */

define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if($USER->IsAuthorized() && check_bitrix_sessid())
{
	$guid = isset($_REQUEST['guid']) ? $_REQUEST['guid'] : '';
	if($guid === '')
	{
		echo 'ERROR: GUID IS EMPTY.';
		die();
	}

	$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
	if($action == 'saveconfig')
	{
		$options = CUserOptions::GetOption('crm.widget_panel', $guid, array());
		CUtil::decodeURIComponent($_POST);
		$options['rows'] = $_POST['rows'];
		CUserOptions::SetOption('crm.widget_panel', $guid, $options);
	}
	elseif($action == 'enabledemo')
	{
		$options = CUserOptions::GetOption('crm.widget_panel', $guid, array());
		CUtil::decodeURIComponent($_POST);
		$options['enableDemoMode'] = isset($_POST['enable']) && strtoupper($_POST['enable']) === 'Y' ? 'Y' : 'N';
		CUserOptions::SetOption('crm.widget_panel', $guid, $options);
	}
	elseif($action == 'savelayout' && isset($_POST['layout']))
	{
		$options = CUserOptions::GetOption('crm.widget_panel', $guid, array());
		CUtil::decodeURIComponent($_POST);
		$layout = $_POST['layout'];
		if(in_array($value, array('L70R30', 'L50R50', 'L30R70'), true))
		{
			$layout = 'L50R50';
		}
		$options['layout'] = $layout;
		CUserOptions::SetOption('crm.widget_panel', $guid, $options);
	}
	elseif($action == 'resetrows')
	{
		$options = CUserOptions::GetOption('crm.widget_panel', $guid, array());
		unset($options['rows']);
		CUserOptions::SetOption('crm.widget_panel', $guid, $options);
	}
	elseif($action == 'resetconfig')
	{
		CUserOptions::DeleteOption('crm.widget_panel', $guid);
	}
	else
	{
		echo 'ERROR: ACTION IS EMPTY OR NOT SUPPORTED.';
		die();
	}
}
echo 'OK';

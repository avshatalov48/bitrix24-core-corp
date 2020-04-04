<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */

/**
 * Bitrix vars
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
	if($action === 'saveconfig')
	{
		$guid = $_POST['guid'];

		$options = CUserOptions::GetOption('crm.entity.channeltracker', $guid, array());

		CUtil::decodeURIComponent($_POST);
		$config = isset($_POST['config']) && is_array($_POST['config']) ? $_POST['config'] : array();

		if(isset($config['expanded']))
		{
			$options['expanded'] = $config['expanded'] === 'Y' ? 'Y' : 'N';
		}

		CUserOptions::SetOption('crm.entity.channeltracker', $guid, $options);
	}
	else
	{
		echo 'ERROR: ACTION IS EMPTY OR NOT SUPPORTED.';
		die();
	}
}
echo 'OK';

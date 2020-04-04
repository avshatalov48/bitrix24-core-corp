<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
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

\Bitrix\Main\Loader::includeModule('crm');

if($USER->IsAuthorized() && check_bitrix_sessid())
{
	CUtil::decodeURIComponent($_POST);
	$guid = isset($_REQUEST['guid']) ? $_REQUEST['guid'] : '';
	if($guid === '')
	{
		echo 'ERROR: GUID IS EMPTY.';
		die();
	}

	$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
	if($action === 'saveconfig')
	{
		$config = isset($_POST['config']) && is_array($_POST['config']) ? $_POST['config'] : array();

		if(isset($_POST['forAllUsers'])
			&& $_POST['forAllUsers'] === 'Y'
			&& CCrmAuthorizationHelper::CanEditOtherSettings()
		)
		{
			if(isset($_POST['delete']) && $_POST['delete'] === 'Y')
			{
				CUserOptions::DeleteOptionsByName('crm.entity.editor', $guid);
			}
			CUserOptions::SetOption('crm.entity.editor', $guid, $config, true);
		}
		CUserOptions::SetOption('crm.entity.editor', $guid, $config);
	}
	elseif($action === 'resetconfig')
	{
		if(isset($_POST['forAllUsers'])
			&& $_POST['forAllUsers'] === 'Y'
			&& CCrmAuthorizationHelper::CanEditOtherSettings()
		)
		{
			CUserOptions::DeleteOptionsByName('crm.entity.editor', $guid);
		}
		else
		{
			CUserOptions::DeleteOption('crm.entity.editor', $guid);
		}
	}
	elseif($action === 'save')
	{
		$scope = isset($_POST['scope'])
			? strtoupper($_POST['scope']) : \Bitrix\Crm\Entity\EntityEditorConfigScope::UNDEFINED;
		if(!\Bitrix\Crm\Entity\EntityEditorConfigScope::isDefined($scope))
		{
			$scope = \Bitrix\Crm\Entity\EntityEditorConfigScope::PERSONAL;
		}

		$config = isset($_POST['config']) && is_array($_POST['config']) ? $_POST['config'] : array();
		$forAllUsers = \CCrmAuthorizationHelper::CanEditOtherSettings()
			&& isset($_POST['forAllUsers'])
			&& $_POST['forAllUsers'] === 'Y';

		if($forAllUsers)
		{
			if(isset($_POST['delete']) && $_POST['delete'] === 'Y')
			{
				CUserOptions::DeleteOptionsByName('crm.entity.editor', $guid);
			}
			CUserOptions::SetOption('crm.entity.editor', $guid, $config, true);
		}

		if($scope === \Bitrix\Crm\Entity\EntityEditorConfigScope::COMMON)
		{
			CUserOptions::SetOption('crm.entity.editor', "{$guid}_common", $config, true);
		}
		else
		{
			CUserOptions::SetOption('crm.entity.editor', $guid, $config);
		}

		$options = isset($_POST['options']) && is_array($_POST['options']) ? $_POST['options'] : array();
		if(!empty($options))
		{
			if($scope === \Bitrix\Crm\Entity\EntityEditorConfigScope::COMMON)
			{
				CUserOptions::SetOption('crm.entity.editor', "{$guid}_common_opts", $options, true);
			}
			else
			{
				$optionID = "{$guid}_opts";
				if($forAllUsers)
				{
					if(isset($_POST['delete']) && $_POST['delete'] === 'Y')
					{
						CUserOptions::DeleteOptionsByName('crm.entity.editor', $optionID);
					}
					CUserOptions::SetOption('crm.entity.editor', $optionID, $options, true);
				}
				CUserOptions::SetOption('crm.entity.editor', $optionID, $options);
			}
		}
	}
	elseif($action === 'reset')
	{
		$scope = isset($_POST['scope'])
			? strtoupper($_POST['scope']) : \Bitrix\Crm\Entity\EntityEditorConfigScope::UNDEFINED;
		if(!\Bitrix\Crm\Entity\EntityEditorConfigScope::isDefined($scope))
		{
			$scope = \Bitrix\Crm\Entity\EntityEditorConfigScope::PERSONAL;
		}

		$forAllUsers = \CCrmAuthorizationHelper::CanEditOtherSettings()
			&& isset($_POST['forAllUsers'])
			&& $_POST['forAllUsers'] === 'Y';

		if($scope === \Bitrix\Crm\Entity\EntityEditorConfigScope::COMMON)
		{
			CUserOptions::DeleteOption('crm.entity.editor', "{$guid}_common", true, 0);
			CUserOptions::DeleteOption('crm.entity.editor', "{$guid}_common_opts", true, 0);
		}
		else
		{
			if($forAllUsers)
			{
				CUserOptions::DeleteOptionsByName('crm.entity.editor', $guid);
				CUserOptions::DeleteOptionsByName('crm.entity.editor', "{$guid}_opts");
				CUserOptions::DeleteOptionsByName('crm.entity.editor', "{$guid}_scope");
			}
			else
			{
				CUserOptions::DeleteOption('crm.entity.editor', $guid);
				CUserOptions::DeleteOption('crm.entity.editor', "{$guid}_opts");
				//CUserOptions::DeleteOption('crm.entity.editor', "{$guid}_scope");

				CUserOptions::SetOption(
					'crm.entity.editor',
					"{$guid}_scope",
					\Bitrix\Crm\Entity\EntityEditorConfigScope::PERSONAL
				);
			}
		}
	}
	elseif($action === 'forceCommonScopeForAll')
	{
		if(\CCrmAuthorizationHelper::CanEditOtherSettings())
		{
			CUserOptions::DeleteOptionsByName('crm.entity.editor', $guid);
			//CUserOptions::DeleteOptionsByName('crm.entity.editor', "{$guid}_opts");
			CUserOptions::DeleteOptionsByName('crm.entity.editor', "{$guid}_scope");
		}
	}
	elseif($action === 'setScope')
	{
		$scope = isset($_POST['scope'])
			? strtoupper($_POST['scope']) : \Bitrix\Crm\Entity\EntityEditorConfigScope::UNDEFINED;

		if(\Bitrix\Crm\Entity\EntityEditorConfigScope::isDefined($scope))
		{
			CUserOptions::SetOption('crm.entity.editor', "{$guid}_scope", $scope);
		}
	}
	else
	{
		echo 'ERROR: ACTION IS EMPTY OR NOT SUPPORTED.';
		die();
	}
}
echo 'OK';

<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);
define('BX_SECURITY_SESSION_READONLY', true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

$errors = array();
if(CModule::IncludeModule('crm'))
{
	$userId = CCrmSecurityHelper::GetCurrentUserID();

	$activityId = (int)$_REQUEST['activity_id'];
	$fileId = (int)$_REQUEST['file_id'];

	$activity = $activityId > 0 ? CCrmActivity::GetByID($activityId, false) : null;

	if ($activity)
	{
		if (
			$userId !== (int)$activity['RESPONSIBLE_ID']
			&& !CCrmActivity::CheckReadPermission($activity['OWNER_TYPE_ID'], $activity['OWNER_ID'])
		)
		{
			$errors[] = 'Access denied!';
		}
		else
		{
			if ((int)$activity['STORAGE_TYPE_ID'] !== \Bitrix\Crm\Integration\StorageType::File)
			{
				$errors[] = 'Access denied!';
			}
			else
			{
				CCrmActivity::PrepareStorageElementIDs($activity);
				if (!in_array($fileId, $activity['STORAGE_ELEMENT_IDS'], true))
				{
					$errors[] = 'Access denied!';
				}
				else
				{
					$fileInfo = CFile::GetFileArray($fileId);
					if(!is_array($fileInfo))
					{
						$errors[] = 'File not found';
					}
					else
					{
						set_time_limit(0);
						CFile::ViewByUser($fileInfo);
					}
				}

			}
		}
	}
	else
	{
		$errors[] = 'Activity not found!';
	}
}
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_after.php");
if(!empty($errors))
{
	foreach($errors as $error)
	{
		echo $error;
	}
}
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog.php");
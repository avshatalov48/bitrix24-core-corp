<?php
namespace Bitrix\Crm\Integration;
use Bitrix\Main\Loader;

class TaskManager
{
	public const TASK_FIELD_NAME = 'UF_CRM_TASK';
	public const TASK_USER_FIELD_ENTITY_ID = 'TASKS_TASK';
	public const TASK_TEMPLATE_USER_FIELD_ENTITY_ID = 'TASKS_TASK_TEMPLATE';

	/**
	* @return \CTaskItem
	*/
	public static function getTaskItem($taskID, $userID)
	{
		if(!Loader::includeModule('tasks'))
		{
			return null;
		}

		if($taskID <= 0 || $userID <= 0)
		{
			return null;
		}

		return new \CTaskItem($taskID, $userID);
	}
	public static function checkUpdatePermission($taskID, $userID = 0)
	{
		if(!is_int($userID))
		{
			$userID = (int)$userID;
		}

		if($userID <= 0)
		{
			$userID = \CCrmSecurityHelper::GetCurrentUserID();
		}

		if(!is_int($taskID))
		{
			$taskID = (int)$taskID;
		}

		$taskItem = self::getTaskItem($taskID, $userID);
		if($taskItem === null)
		{
			return false;
		}

		try
		{
			if (!class_exists('\Bitrix\Tasks\Access\ActionDictionary'))
			{
				return $taskItem->isActionAllowed(\CTaskItem::ACTION_EDIT);
			}
			return $taskItem->checkAccess(\Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_EDIT);
		}
		catch(\TasksException $e)
		{
			return false;
		}
	}
	public static function checkDeletePermission($taskID, $userID = 0)
	{
		if(!is_int($userID))
		{
			$userID = (int)$userID;
		}

		if($userID <= 0)
		{
			$userID = \CCrmSecurityHelper::GetCurrentUserID();
		}

		if(!is_int($taskID))
		{
			$taskID = (int)$taskID;
		}

		$taskItem = self::getTaskItem($taskID, $userID);
		if($taskItem === null)
		{
			return false;
		}

		try
		{
			if (!class_exists('\Bitrix\Tasks\Access\ActionDictionary'))
			{
				return $taskItem->isActionAllowed(\CTaskItem::ACTION_REMOVE);
			}
			return $taskItem->checkAccess(\Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_REMOVE);
		}
		catch(\TasksException $e)
		{
			return false;
		}
	}
	public static function checkCompletePermission($taskID, $userID = 0)
	{
		if(!is_int($userID))
		{
			$userID = (int)$userID;
		}

		if($userID <= 0)
		{
			$userID = \CCrmSecurityHelper::GetCurrentUserID();
		}

		if(!is_int($taskID))
		{
			$taskID = (int)$taskID;
		}

		$taskItem = self::getTaskItem($taskID, $userID);
		if($taskItem === null)
		{
			return false;
		}

		try
		{
			if (!class_exists('\Bitrix\Tasks\Access\ActionDictionary'))
			{
				return $taskItem->isActionAllowed(\CTaskItem::ACTION_COMPLETE);
			}
			return $taskItem->checkAccess(\Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_COMPLETE);
		}
		catch(\TasksException $e)
		{
			return false;
		}
	}
	public static function checkRenewPermission($taskID, $userID = 0)
	{
		if(!is_int($userID))
		{
			$userID = (int)$userID;
		}

		if($userID <= 0)
		{
			$userID = \CCrmSecurityHelper::GetCurrentUserID();
		}

		if(!is_int($taskID))
		{
			$taskID = (int)$taskID;
		}

		$taskItem = self::getTaskItem($taskID, $userID);
		if($taskItem === null)
		{
			return false;
		}

		try
		{
			if (!class_exists('\Bitrix\Tasks\Access\ActionDictionary'))
			{
				return $taskItem->isActionAllowed(\CTaskItem::ACTION_RENEW);
			}
			return $taskItem->checkAccess(\Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_RENEW);
		}
		catch(\TasksException $e)
		{
			return false;
		}
	}
}

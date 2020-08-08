<?php

namespace Bitrix\Tasks\Ui\Preview;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Util\User;

Loc::loadLanguageFile(__FILE__);

class Task
{
	public static function buildPreview(array $params)
	{
		global $APPLICATION;
		$taskId = (int)$params['taskId'];
		if(!$taskId)
		{
			return '';
		}

		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:tasks.task.preview',
			'',
			$params
		);
		return ob_get_clean();
	}

	public static function checkUserReadAccess(array $params)
	{
		$taskId = (int)$params['taskId'];
		if(!$taskId)
		{
			return false;
		}
		$task = new \CTaskItem($taskId, static::getUser()->GetID());
		$access = $task->checkCanRead();

		return !!$access;
	}

	public static function getImAttach(array $params)
	{
		if(!Loader::includeModule('im'))
		{
			return false;
		}

		$taskId = (int)$params['taskId'];
		if(!$taskId)
		{
			return false;
		}

		$task = \CTasks::getById(
			$taskId,
			false,
			array(
				'returnAsArray'  => true,
				'bSkipExtraData' => false
			)
		);
		if($task === false)
			return false;

		$task['LINK'] = \CTaskNotifications::getNotificationPath(array('ID' => $task['RESPONSIBLE_ID']), $task['ID']);

		$attach = new \CIMMessageParamAttach(1, '#E30000');
		$attach->AddUser(Array(
			'NAME' => \CTextParser::clearAllTags($task['TITLE']),
			//'AVATAR' => '', // todo: task icon
			'LINK' => $task['LINK']
		));
		$attach->AddDelimiter();
		$grid = array();
		if($task['STATUS'] > 0)
		{
			$grid[] = Array(
				"NAME" => Loc::getMessage('TASK_PREVIEW_FIELD_STATUS') . ":",
				"VALUE" => Loc::getMessage('TASKS_TASK_STATUS_'.$task['STATUS']),
				"DISPLAY" => "COLUMN",
				"WIDTH" => 120,
			);
		}

		$grid[] = Array(
			"NAME" => Loc::getMessage('TASK_PREVIEW_FIELD_ASSIGNER') . ":",
			"VALUE" => htmlspecialcharsback(\Bitrix\Im\User::getInstance($task['CREATED_BY'])->getFullName()),
			"USER_ID" => $task['CREATED_BY'],
			"DISPLAY" => "COLUMN",
			"WIDTH" => 120,
		);

		$grid[] = Array(
			"NAME" => Loc::getMessage('TASK_PREVIEW_FIELD_RESPONSIBLE') . ":",
			"VALUE" => htmlspecialcharsback(\Bitrix\Im\User::getInstance($task['RESPONSIBLE_ID'])->getFullName()),
			"USER_ID" => $task['RESPONSIBLE_ID'],
			"DISPLAY" => "COLUMN",
			"WIDTH" => 120,
		);

		if($task['DEADLINE'] != '')
		{
			$grid[] = Array(
				"NAME" => Loc::getMessage('TASK_PREVIEW_FIELD_DEADLINE') . ":",
				"VALUE" => $task['DEADLINE'],
				"DISPLAY" => "COLUMN",
				"WIDTH" => 120,
			);
		}

		if($task['DESCRIPTION'] != '')
		{
			$description = \CTextParser::clearAllTags($task['DESCRIPTION']);
			if(mb_strlen($description) > 100)
			{
				$description = mb_substr($description, 0, 100).'...';
			}

			$grid[] = Array(
				"NAME" => Loc::getMessage('TASK_PREVIEW_FIELD_DESCRIPTION') . ":",
				"VALUE" => $description,
				"DISPLAY" => "COLUMN",
				"WIDTH" => 120,
			);
		}

		if($task['GROUP_ID'] > 0)
		{
			$groupId = $task['GROUP_ID'];
			$groupData = \Bitrix\Tasks\Integration\SocialNetwork\Group::getData(array($groupId));
			if(is_array($groupData[$groupId]))
			{
				$groupName = $groupData[$groupId]['NAME'];
				$grid[] = Array(
					"NAME" => Loc::getMessage("TASK_PREVIEW_FIELD_GROUP") . ":",
					"VALUE" => $groupName,
					"DISPLAY" => "COLUMN",
					"WIDTH" => 120,
				);
			}
		}

		$attach->AddGrid($grid);
		return $attach;
	}

	protected function getUser()
	{
		return User::get();
	}
}
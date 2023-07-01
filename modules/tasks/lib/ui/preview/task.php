<?php

namespace Bitrix\Tasks\Ui\Preview;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
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

		try
		{
			$task = new \CTaskItem($taskId, static::getUser()->GetID());
		}
		catch (\CTaskAssertException $e)
		{
			return false;
		}

		$access = $task->checkCanRead();

		return !!$access;
	}

	public static function getImAttach(array $params)
	{
		if (!Loader::includeModule('im'))
		{
			return false;
		}

		$taskId = (int)$params['taskId'];
		if (!$taskId)
		{
			return false;
		}

		$task = new \CTaskItem($taskId, static::getUser()->getId());
		if (!$task)
		{
			return false;
		}

		try
		{
			$select = ['ID', 'TITLE', 'DESCRIPTION', 'CREATED_BY', 'RESPONSIBLE_ID', 'STATUS', 'DEADLINE', 'GROUP_ID'];
			$taskData = $task->getData(false, ['select' => $select], false);
		}
		catch (\TasksException $exception)
		{
			return false;
		}

		$taskData['LINK'] = \CTaskNotifications::getNotificationPath(
			['ID' => $taskData['RESPONSIBLE_ID']],
			$taskData['ID']
		);

		$attach = new \CIMMessageParamAttach(1, '#E30000');
		$attach->AddUser([
			'NAME' => \CTextParser::clearAllTags($taskData['TITLE']),
			'LINK' => $taskData['LINK'],
		]);
		$attach->AddDelimiter();
		$attach->AddGrid(static::getImAttachGrid($taskData));

		return $attach;
	}

	public static function getImRich(array $params)
	{
		if (!Loader::includeModule('im'))
		{
			return false;
		}

		if (!class_exists('\Bitrix\Im\V2\Entity\Url\RichData'))
		{
			return false;
		}

		$taskId = (int)$params['taskId'];
		if (!$taskId)
		{
			return false;
		}

		$task = new \CTaskItem($taskId, static::getUser()->getId());
		if (!$task)
		{
			return false;
		}

		try
		{
			$select = ['ID', 'TITLE', 'DESCRIPTION', 'RESPONSIBLE_ID', 'CREATED_BY', 'AUDITORS', 'ACCOMPLICES'];
			$taskData = $task->getData(false, ['select' => $select], false);
		}
		catch (\TasksException $exception)
		{
			return false;
		}

		$membersIds = array_merge(
			[(int)$taskData['CREATED_BY']],
			[(int)$taskData['RESPONSIBLE_ID']],
			array_map(static fn ($id) => (int)$id, $taskData['AUDITORS'] ?? []),
			array_map(static fn ($id) => (int)$id, $taskData['ACCOMPLICES'] ?? [])
		);
		$membersIds = array_values(array_unique($membersIds));

		$richData = new \Bitrix\Im\V2\Entity\Url\RichData();
		$link = \CTaskNotifications::getNotificationPath(
			['ID' => $taskData['RESPONSIBLE_ID']],
			$taskData['ID']
		);

		$richData
			->setType(\Bitrix\Im\V2\Entity\Url\RichData::TASKS_TYPE)
			->setName(\CTextParser::clearAllTags($taskData['TITLE']))
			->setDescription(\CTextParser::clearAllTags($taskData['DESCRIPTION']))
			->setLink($link)
			->setAllowedUsers($membersIds)
		;

		return $richData;
	}

	protected static function getImAttachGrid(array $taskData): array
	{
		$grid = [];
		$display = 'COLUMN';
		$columnWidth = 120;

		if ($taskData['STATUS'] > 0)
		{
			$grid[] = [
				'NAME' => Loc::getMessage('TASK_PREVIEW_FIELD_STATUS') . ':',
				'VALUE' => Loc::getMessage('TASKS_TASK_STATUS_' . $taskData['STATUS']),
				'DISPLAY' => $display,
				'WIDTH' => $columnWidth,
			];
		}

		$grid[] = [
			'NAME' => Loc::getMessage('TASK_PREVIEW_FIELD_ASSIGNER') . ':',
			'VALUE' => \Bitrix\Im\User::getInstance($taskData['CREATED_BY'])->getFullName(false),
			'USER_ID' => $taskData['CREATED_BY'],
			'DISPLAY' => $display,
			'WIDTH' => $columnWidth,
		];

		$grid[] = [
			'NAME' => Loc::getMessage('TASK_PREVIEW_FIELD_RESPONSIBLE') . ':',
			'VALUE' => \Bitrix\Im\User::getInstance($taskData['RESPONSIBLE_ID'])->getFullName(false),
			'USER_ID' => $taskData['RESPONSIBLE_ID'],
			'DISPLAY' => $display,
			'WIDTH' => $columnWidth,
		];

		if ((string)$taskData['DEADLINE'] !== '')
		{
			$grid[] = [
				'NAME' => Loc::getMessage('TASK_PREVIEW_FIELD_DEADLINE') . ':',
				'VALUE' => $taskData['DEADLINE'],
				'DISPLAY' => $display,
				'WIDTH' => $columnWidth,
			];
		}

		if ((string)$taskData['DESCRIPTION'] !== '')
		{
			$description = \CTextParser::clearAllTags(
				htmlspecialchars_decode(htmlspecialcharsback($taskData['DESCRIPTION']), ENT_QUOTES)
			);
			if (mb_strlen($description) > 100)
			{
				$description = mb_substr($description, 0, 100) . '...';
			}

			$grid[] = [
				'NAME' => Loc::getMessage('TASK_PREVIEW_FIELD_DESCRIPTION') . ':',
				'VALUE' => $description,
				'DISPLAY' => $display,
				'WIDTH' => $columnWidth,
			];
		}

		if ($taskData['GROUP_ID'] > 0)
		{
			$groupId = $taskData['GROUP_ID'];
			$groupData = Group::getData([$groupId]);

			if (is_array($groupData[$groupId]))
			{
				$grid[] = [
					'NAME' => Loc::getMessage('TASK_PREVIEW_FIELD_GROUP') . ':',
					'VALUE' => $groupData[$groupId]['NAME'],
					'DISPLAY' => $display,
					'WIDTH' => $columnWidth,
				];
			}
		}

		return $grid;
	}

	protected static function getUser()
	{
		return User::get();
	}
}
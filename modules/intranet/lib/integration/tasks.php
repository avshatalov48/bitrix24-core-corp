<?php
namespace Bitrix\Intranet\Integration;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

final class Tasks
{
	public static function createDemoTemplates(): void
	{
		if (
			!Loader::includeModule('tasks')
			|| !($adminId = User::getAdminId())
		)
		{
			return;
		}

		$map = [
			'SONET_INITIAL_TASK' => [
				'TITLE' => Loc::getMessage('SONET_TASK_TITLE'),
				'DESCRIPTION' => Loc::getMessage('SONET_TASK_DESCRIPTION'),
			],
			'SONET_INVITE_TASK' => [
				"TITLE" => Loc::getMessage('SONET_INVITE_TASK_TITLE'),
				"DESCRIPTION" => Loc::getMessage('SONET_INVITE_TASK_DESCRIPTION_V2'),
			],
			'SONTE_INSTALL_APP_TASK' => [
				'TITLE' => Loc::getMessage('SONET_INSTALL_APP_TASK_TITLE'),
				'DESCRIPTION' => Loc::getMessage('SONET_INSTALL_APP_TASK_DESCRIPTION'),
			],
		];
		foreach ($map as $xmlId => $data)
		{
			$order = $navParams = $params = false;
			$filter = [
				'XML_ID' => $xmlId,
				'CREATED_BY' => $adminId,
			];
			$select = ['ID'];

			$templateResult = \CTaskTemplates::GetList($order, $filter, $navParams, $params, $select);
			if (!$templateResult->Fetch())
			{
				(new \CTaskTemplates())->Add([
					'CREATED_BY' => $adminId,
					'TPARAM_TYPE' => \CTaskTemplates::TYPE_FOR_NEW_USER,
					'PRIORITY' => \CTasks::PRIORITY_AVERAGE,
					'STATUS' => \CTasks::STATE_PENDING,
					'TITLE' => $data['TITLE'],
					'DESCRIPTION' => $data['DESCRIPTION'],
					'DESCRIPTION_IN_BBCODE' => 'Y',
					'SITE_ID' => \CTaskTemplates::CURRENT_SITE_ID,
					'XML_ID' => $xmlId,
					'ALLOW_CHANGE_DEADLINE' => 'Y',
				]);
			}
		}
	}

	public static function createDemoTasksForUser(int $userId): void
	{
		if (!Loader::includeModule('tasks'))
		{
			return;
		}

		$templateResult = \CTaskTemplates::GetList(
			false,
			[
				'TPARAM_TYPE' => \CTaskTemplates::TYPE_FOR_NEW_USER,
				'BASE_TEMPLATE_ID' => false,
				'!XML_ID' => [
					'SONET_INITIAL_TASK',
					'SONET_INVITE_TASK',
					'SONTE_INSTALL_APP_TASK',
				],
			],
			false,
			false,
			['ID', 'CREATED_BY']
		);
		while ($item = $templateResult->Fetch())
		{
			\CTaskItem::addByTemplate(
				$item['ID'],
				$item['CREATED_BY'],
				false,
				['BEFORE_ADD_CALLBACK' => self::getBeforeAddCallback($userId)]
			);
		}
	}

	private static function getBeforeAddCallback(int $userId): \Closure
	{
		return static function (&$fields) use ($userId) {
			if (!(int)$fields['RESPONSIBLE_ID'])
			{
				$fields['RESPONSIBLE_ID'] = $userId;
			}
			$fields['XML_ID'] = md5($fields['TITLE'] . $fields['DESCRIPTION'] . SITE_ID);
			$fields['STATUS'] = \CTasks::STATE_PENDING;
			$fields['SITE_ID'] = SITE_ID;

			return true;
		};
	}
}
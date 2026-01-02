<?php

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */

use Bitrix\Main\Localization\Loc;

IncludeModuleLangFile(__FILE__);

class CTasksNotifySchema
{
	public function __construct()
	{
	}

	/**
	 * @return array[][]
	 */
	public static function OnGetNotifySchema(): array
	{
		return [
			'tasks' => [
				'comment' => [
					'NAME' => Loc::getMessage('TASKS_NS_COMMENT'),
					'PUSH' => 'Y',
					'MAIL' => 'N',
					'XMPP' => 'N',
					'DISABLED' => [IM_NOTIFY_FEATURE_XMPP],
				],
				'reminder' => [
					'NAME' => Loc::getMessage('TASKS_NS_REMINDER'),
					'PUSH' => 'Y',
				],
				'manage' => [
					'NAME' => Loc::getMessage('TASKS_NS_MANAGE_MSGVER_1'),
					'PUSH' => 'Y',
				],
				'task_assigned' => [
					'NAME' => Loc::getMessage('TASKS_NS_TASK_ASSIGNED_MSGVER_1'),
					'PUSH' => 'Y',
				],
				'task_expired_soon' => [
					'NAME' => Loc::getMessage('TASKS_NS_TASK_EXPIRED_SOON'),
					'PUSH' => 'Y',
					'MAIL' => 'N',
					'XMPP' => 'N',
					'DISABLED' => [IM_NOTIFY_FEATURE_XMPP, IM_NOTIFY_FEATURE_MAIL],
				],
			],
		];
	}
}


class CTasksPullSchema
{
	public static function OnGetDependentModule()
	{
		return [
			'MODULE_ID' => 'tasks',
			'USE'       => ['PUBLIC_SECTION'],
		];
	}
}

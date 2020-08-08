<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */


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
					'NAME' => GetMessage('TASKS_NS_COMMENT'),
					'PUSH' => 'Y',
					'MAIL' => 'N',
					'XMPP' => 'N',
					'DISABLED' => [IM_NOTIFY_FEATURE_XMPP],
				],
				'reminder' => [
					'NAME' => GetMessage('TASKS_NS_REMINDER'),
					'PUSH' => 'Y',
				],
				'manage' => [
					'NAME' => GetMessage('TASKS_NS_MANAGE'),
					'PUSH' => 'Y',
				],
				'task_assigned' => [
					'NAME' => GetMessage('TASKS_NS_TASK_ASSIGNED'),
					'PUSH' => 'Y',
				],
			],
		];
	}
}


class CTasksPullSchema
{
	public static function OnGetDependentModule()
	{
		return array(
			'MODULE_ID' => 'tasks',
			'USE'       => array('PUBLIC_SECTION')
		);
	}
}

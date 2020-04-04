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


	public static function OnGetNotifySchema()
	{
		return array(
			'tasks' => array(
				'comment' => array(
					'NAME'      => GetMessage('TASKS_NS_COMMENT'),
					'PUSH' 		=> 'Y'
				),
				'reminder' => array(
					'NAME'      => GetMessage('TASKS_NS_REMINDER'),
					'PUSH' 		=> 'Y'
				),
				'manage' => array(
					'NAME'      => GetMessage('TASKS_NS_MANAGE'),
					'PUSH' 		=> 'Y'
				),
				'task_assigned' => array(
					'NAME'      => GetMessage('TASKS_NS_TASK_ASSIGNED'),
					'PUSH' 		=> 'Y'
				),
			),
		);
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

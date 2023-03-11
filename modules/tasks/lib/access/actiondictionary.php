<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access;

class ActionDictionary
{
	public const
		ACTION_TASK_READ 					= 'task_read',
		ACTION_TASK_EDIT					= 'task_edit',
		ACTION_TASK_SAVE		 			= 'task_save',
		ACTION_TASK_REMOVE 					= 'task_remove',
		ACTION_TASK_ACCEPT 					= 'task_accept',
		ACTION_TASK_DECLINE 				= 'task_decline',
		ACTION_TASK_COMPLETE 				= 'task_complete',
		ACTION_TASK_APPROVE 				= 'task_approve',
		ACTION_TASK_DISAPPROVE 				= 'task_disapprove',
		ACTION_TASK_START 					= 'task_start',
		ACTION_TASK_DELEGATE 				= 'task_delegate',
		ACTION_TASK_DEFER	 				= 'task_defer',
		ACTION_TASK_RENEW	 				= 'task_renew',
		ACTION_TASK_CREATE	 				= 'task_create',
		ACTION_TASK_DEADLINE 				= 'task_deadline',
		ACTION_TASK_CHANGE_DIRECTOR			= 'task_change_director',
		ACTION_TASK_CHANGE_RESPONSIBLE		= 'task_change_responsible',
		ACTION_TASK_CHANGE_ACCOMPLICES		= 'task_change_accomplices',
		ACTION_TASK_PAUSE					= 'task_pause',
		ACTION_TASK_TIME_TRACKING			= 'task_time_tracking',
		ACTION_TASK_RATE					= 'task_rate',
		ACTION_TASK_CHANGE_STATUS			= 'task_change_status',
		ACTION_TASK_REMINDER				= 'task_reminder',
		ACTION_TASK_AUDITORS_ADD			= 'task_auditors_add',

		ACTION_TASK_ELAPSED_TIME			= 'task_elapsed_time',
		ACTION_TASK_FAVORITE_ADD			= 'task_favorite_add',
		ACTION_TASK_FAVORITE_DELETE			= 'task_favorite_delete',
		ACTION_TASK_FAVORITE				= 'task_favorite',

		ACTION_CHECKLIST_ADD				= 'checklist_add',
		ACTION_CHECKLIST_EDIT				= 'checklist_edit',
		ACTION_CHECKLIST_SAVE				= 'checklist_save',
		ACTION_CHECKLIST_TOGGLE				= 'checklist_toggle',

		ACTION_TEMPLATE_READ				= 'template_read',
		ACTION_TEMPLATE_EDIT				= 'template_edit',
		ACTION_TEMPLATE_REMOVE				= 'template_remove',
		ACTION_TEMPLATE_CREATE				= 'template_create',
		ACTION_TEMPLATE_SAVE				= 'template_save',

		ACTION_TASK_EXPORT					= 'task_export',
		ACTION_TASK_IMPORT					= 'task_import',

		ACTION_TASK_ROBOT_EDIT				= 'task_robot_edit',

		ACTION_TASK_RESULT_EDIT				= 'task_result_edit',
		ACTION_TASK_COMPLETE_RESULT			= 'task_complete_result',

		ACTION_TASK_ADMIN					= 'task_admin',

		ACTION_TAG_EDIT						= 'tag_edit',
		ACTION_TAG_DELETE					= 'tag_delete',
		ACTION_TAG_CREATE					= 'tag_create',
		ACTION_TAG_SEARCH					= 'tag_search';

	public static function getLegacyActionMap(): array
	{
		return [
			\CTaskItem::ACTION_ACCEPT     				=> self::ACTION_TASK_ACCEPT,
			\CTaskItem::ACTION_DECLINE    				=> self::ACTION_TASK_DECLINE,
			\CTaskItem::ACTION_COMPLETE   				=> self::ACTION_TASK_COMPLETE,
			\CTaskItem::ACTION_APPROVE    				=> self::ACTION_TASK_APPROVE,
			\CTaskItem::ACTION_DISAPPROVE 				=> self::ACTION_TASK_DISAPPROVE,
			\CTaskItem::ACTION_START      				=> self::ACTION_TASK_START,
			\CTaskItem::ACTION_DELEGATE   				=> self::ACTION_TASK_DELEGATE,
			\CTaskItem::ACTION_REMOVE     				=> self::ACTION_TASK_REMOVE,
			\CTaskItem::ACTION_EDIT       				=> self::ACTION_TASK_EDIT,
			\CTaskItem::ACTION_DEFER      				=> self::ACTION_TASK_DEFER,
			\CTaskItem::ACTION_RENEW      				=> self::ACTION_TASK_RENEW,
			\CTaskItem::ACTION_CREATE     				=> self::ACTION_TASK_CREATE,
			\CTaskItem::ACTION_CHANGE_DEADLINE     		=> self::ACTION_TASK_DEADLINE,
			\CTaskItem::ACTION_CHANGE_DIRECTOR     		=> self::ACTION_TASK_CHANGE_DIRECTOR,
			\CTaskItem::ACTION_PAUSE               		=> self::ACTION_TASK_PAUSE,
			\CTaskItem::ACTION_START_TIME_TRACKING 		=> self::ACTION_TASK_TIME_TRACKING,

			\CTaskItem::ACTION_CHECKLIST_ADD_ITEMS 		=> self::ACTION_CHECKLIST_ADD,
			\CTaskItem::ACTION_CHECKLIST_REORDER_ITEMS 	=> self::ACTION_CHECKLIST_EDIT,

			\CTaskItem::ACTION_ELAPSED_TIME_ADD    		=> self::ACTION_TASK_ELAPSED_TIME,

			\CTaskItem::ACTION_ADD_FAVORITE        		=> self::ACTION_TASK_FAVORITE_ADD,
			\CTaskItem::ACTION_DELETE_FAVORITE     		=> self::ACTION_TASK_FAVORITE_DELETE,
			\CTaskItem::ACTION_TOGGLE_FAVORITE     		=> self::ACTION_TASK_FAVORITE,

			\CTaskItem::ACTION_READ                		=> self::ACTION_TASK_READ,
			\CTaskItem::ACTION_RATE						=> self::ACTION_TASK_RATE
		];
	}

	public static function getActionByLegacyId($actionId): ?string
	{
		$map = self::getLegacyActionMap();
		if (array_key_exists($actionId, $map))
		{
			return $map[$actionId];
		}
		return null;
	}
}
<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access;

use CTaskItem;

class ActionDictionary
{
	public const ACTION_TASK_READ = 'task_read';
	public const ACTION_TASK_EDIT = 'task_edit';
	public const ACTION_TASK_SAVE = 'task_save';
	public const ACTION_TASK_REMOVE = 'task_remove';
	public const ACTION_TASK_ACCEPT = 'task_accept';
	public const ACTION_TASK_DECLINE = 'task_decline';
	public const ACTION_TASK_COMPLETE = 'task_complete';
	public const ACTION_TASK_APPROVE = 'task_approve';
	public const ACTION_TASK_DISAPPROVE = 'task_disapprove';
	public const ACTION_TASK_START = 'task_start';
	public const ACTION_TASK_TAKE = 'task_take';
	public const ACTION_TASK_DELEGATE = 'task_delegate';
	public const ACTION_TASK_DEFER = 'task_defer';
	public const ACTION_TASK_RENEW = 'task_renew';
	public const ACTION_TASK_CREATE = 'task_create';
	public const ACTION_TASK_DEADLINE = 'task_deadline';
	public const ACTION_TASK_CHANGE_DIRECTOR = 'task_change_director';
	public const ACTION_TASK_CHANGE_RESPONSIBLE = 'task_change_responsible';
	public const ACTION_TASK_CHANGE_ACCOMPLICES = 'task_change_accomplices';
	public const ACTION_TASK_PAUSE = 'task_pause';
	public const ACTION_TASK_TIME_TRACKING = 'task_time_tracking';
	public const ACTION_TASK_RATE = 'task_rate';
	public const ACTION_TASK_CHANGE_STATUS = 'task_change_status';
	public const ACTION_TASK_REMINDER = 'task_reminder';
	public const ACTION_TASK_ADD_AUDITORS = 'task_add_auditors';

	public const ACTION_TASK_ELAPSED_TIME = 'task_elapsed_time';
	public const ACTION_TASK_FAVORITE_ADD = 'task_favorite_add';
	public const ACTION_TASK_FAVORITE_DELETE = 'task_favorite_delete';
	public const ACTION_TASK_FAVORITE = 'task_favorite';

	public const ACTION_CHECKLIST_ADD = 'checklist_add';
	public const ACTION_CHECKLIST_EDIT = 'checklist_edit';
	public const ACTION_CHECKLIST_SAVE = 'checklist_save';
	public const ACTION_CHECKLIST_TOGGLE = 'checklist_toggle';

	public const ACTION_TEMPLATE_READ = 'template_read';
	public const ACTION_TEMPLATE_EDIT = 'template_edit';
	public const ACTION_TEMPLATE_REMOVE = 'template_remove';
	public const ACTION_TEMPLATE_CREATE = 'template_create';
	public const ACTION_TEMPLATE_SAVE = 'template_save';

	public const ACTION_TASK_EXPORT = 'task_export';
	public const ACTION_TASK_IMPORT = 'task_import';

	public const ACTION_TASK_ROBOT_EDIT = 'task_robot_edit';

	public const ACTION_TASK_RESULT_EDIT = 'task_result_edit';
	public const ACTION_TASK_COMPLETE_RESULT = 'task_complete_result';
	public const ACTION_TASK_REMOVE_RESULT = 'task_remove_result';

	public const ACTION_TASK_ADMIN = 'task_admin';

	public const ACTION_TAG_EDIT = 'tag_edit';
	public const ACTION_TAG_DELETE = 'tag_delete';
	public const ACTION_TAG_CREATE = 'tag_create';
	public const ACTION_TAG_SEARCH = 'tag_search';
	public const ACTION_GROUP_TAG_READ = 'group_tag_read';

	public static function getLegacyActionMap(): array
	{
		return [
			CTaskItem::ACTION_ACCEPT => self::ACTION_TASK_ACCEPT,
			CTaskItem::ACTION_DECLINE => self::ACTION_TASK_DECLINE,
			CTaskItem::ACTION_COMPLETE => self::ACTION_TASK_COMPLETE,
			CTaskItem::ACTION_APPROVE => self::ACTION_TASK_APPROVE,
			CTaskItem::ACTION_DISAPPROVE => self::ACTION_TASK_DISAPPROVE,
			CTaskItem::ACTION_START => self::ACTION_TASK_START,
			CTaskItem::ACTION_DELEGATE => self::ACTION_TASK_DELEGATE,
			CTaskItem::ACTION_REMOVE => self::ACTION_TASK_REMOVE,
			CTaskItem::ACTION_EDIT => self::ACTION_TASK_EDIT,
			CTaskItem::ACTION_DEFER => self::ACTION_TASK_DEFER,
			CTaskItem::ACTION_RENEW => self::ACTION_TASK_RENEW,
			CTaskItem::ACTION_CREATE => self::ACTION_TASK_CREATE,
			CTaskItem::ACTION_CHANGE_DEADLINE => self::ACTION_TASK_DEADLINE,
			CTaskItem::ACTION_CHANGE_DIRECTOR => self::ACTION_TASK_CHANGE_DIRECTOR,
			CTaskItem::ACTION_PAUSE => self::ACTION_TASK_PAUSE,
			CTaskItem::ACTION_START_TIME_TRACKING => self::ACTION_TASK_TIME_TRACKING,
			CTaskItem::ACTION_CHECKLIST_ADD_ITEMS => self::ACTION_CHECKLIST_ADD,
			CTaskItem::ACTION_CHECKLIST_REORDER_ITEMS => self::ACTION_CHECKLIST_EDIT,
			CTaskItem::ACTION_ELAPSED_TIME_ADD => self::ACTION_TASK_ELAPSED_TIME,
			CTaskItem::ACTION_ADD_FAVORITE => self::ACTION_TASK_FAVORITE_ADD,
			CTaskItem::ACTION_DELETE_FAVORITE => self::ACTION_TASK_FAVORITE_DELETE,
			CTaskItem::ACTION_TOGGLE_FAVORITE => self::ACTION_TASK_FAVORITE,
			CTaskItem::ACTION_READ => self::ACTION_TASK_READ,
			CTaskItem::ACTION_RATE => self::ACTION_TASK_RATE,
			CTaskItem::ACTION_TAKE => self::ACTION_TASK_TAKE,
		];
	}

	public static function getActionByLegacyId($actionId): ?string
	{
		$map = self::getLegacyActionMap();

		return $map[$actionId] ?? null;
	}
}
<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Counter\Event;


class EventDictionary
{
	public const
		EVENT_AFTER_TASK_VIEW 			= 'onAfterTaskView',

		EVENT_AFTER_TASK_ADD 			= 'onAfterTaskAdd',
		EVENT_AFTER_TASK_DELETE 		= 'onAfterTaskDelete',
		EVENT_AFTER_TASK_UPDATE 		= 'onAfterTaskUpdate',
		EVENT_AFTER_TASK_RESTORE 		= 'onAfterTaskRestore',
		EVENT_AFTER_TASK_MUTE 			= 'onAfterTaskMute',

		EVENT_AFTER_COMMENT_ADD 		= 'onAfterCommentAdd',
		EVENT_AFTER_COMMENT_DELETE 		= 'onAfterCommentDelete',
		EVENT_AFTER_COMMENTS_READ_ALL 	= 'onAfterCommentsReadAll',
		EVENT_AFTER_PROJECT_READ_ALL 	= 'onAfterProjectReadAll',
		EVENT_AFTER_SCRUM_READ_ALL 		= 'onAfterScrumReadAll',

		EVENT_TASK_EXPIRED 				= 'onTaskExpired',
		EVENT_TASK_EXPIRED_SOON			= 'onTaskExpiredSoon',

		EVENT_PROJECT_PERM_UPDATE		= 'onProjectPermUpdate',
		EVENT_PROJECT_DELETE			= 'onProjectDelete',
		EVENT_PROJECT_USER_ADD			= 'onProjectUserAdd',
		EVENT_PROJECT_USER_UPDATE		= 'onProjectUserUpdate',
		EVENT_PROJECT_USER_DELETE		= 'onProjectUserDelete',

		EVENT_GARBAGE_COLLECT			= 'onGarbageCollect';
}
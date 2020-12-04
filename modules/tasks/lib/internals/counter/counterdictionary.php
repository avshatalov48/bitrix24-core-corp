<?php
namespace Bitrix\Tasks\Internals\Counter;

use Bitrix\Tasks\Internals\Task\MemberTable;

/**
 * Class CounterDictionary
 *
 * @package Bitrix\Tasks\Internals\Counter
 */
class CounterDictionary
{
	public const PREFIX 				= 'tasks_';

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

		EVENT_TASK_EXPIRED 				= 'onTaskExpired',
		EVENT_TASK_EXPIRED_SOON			= 'onTaskExpiredSoon';

	public const
		COUNTER_TOTAL							= 'total',

		COUNTER_NEW_COMMENTS					= 'new_comments',
		COUNTER_EXPIRED							= 'expired',
		COUNTER_EFFECTIVE						= 'effective',

		COUNTER_MY								= 'my',
		COUNTER_MY_EXPIRED						= 'my_expired',
		COUNTER_MY_MUTED_EXPIRED				= 'my_muted_expired',
		COUNTER_MY_NEW_COMMENTS					= 'my_new_comments',
		COUNTER_MY_MUTED_NEW_COMMENTS			= 'my_muted_new_comments',

		COUNTER_ACCOMPLICES						= 'accomplices',
		COUNTER_ACCOMPLICES_EXPIRED				= 'accomplices_expired',
		COUNTER_ACCOMPLICES_MUTED_EXPIRED		= 'accomplices_muted_expired',
		COUNTER_ACCOMPLICES_NEW_COMMENTS 		= 'accomplices_new_comments',
		COUNTER_ACCOMPLICES_MUTED_NEW_COMMENTS 	= 'accomplices_muted_new_comments',

		COUNTER_AUDITOR							= 'auditor',
		COUNTER_AUDITOR_EXPIRED					= 'auditor_expired',
		COUNTER_AUDITOR_MUTED_EXPIRED			= 'auditor_muted_expired',
		COUNTER_AUDITOR_NEW_COMMENTS			= 'auditor_new_comments',
		COUNTER_AUDITOR_MUTED_NEW_COMMENTS		= 'auditor_muted_new_comments',

		COUNTER_ORIGINATOR						= 'originator',
		COUNTER_ORIGINATOR_EXPIRED				= 'originator_expired',
		COUNTER_ORIGINATOR_MUTED_EXPIRED		= 'originator_muted_expired',
		COUNTER_ORIGINATOR_NEW_COMMENTS			= 'originator_new_comments',
		COUNTER_ORIGINATOR_MUTED_NEW_COMMENTS	= 'originator_muted_new_comments',

		COUNTER_FLAG_COUNTED					= 'flag_computed';

	public const MAP_EXPIRED = [
		MemberTable::MEMBER_TYPE_RESPONSIBLE 	=> self::COUNTER_MY_EXPIRED,
		MemberTable::MEMBER_TYPE_ORIGINATOR 	=> self::COUNTER_ORIGINATOR_EXPIRED,
		MemberTable::MEMBER_TYPE_ACCOMPLICE 	=> self::COUNTER_ACCOMPLICES_EXPIRED,
		MemberTable::MEMBER_TYPE_AUDITOR 		=> self::COUNTER_AUDITOR_EXPIRED
	];

	public const MAP_MUTED_EXPIRED = [
		MemberTable::MEMBER_TYPE_RESPONSIBLE 	=> self::COUNTER_MY_MUTED_EXPIRED,
		MemberTable::MEMBER_TYPE_ORIGINATOR 	=> self::COUNTER_ORIGINATOR_MUTED_EXPIRED,
		MemberTable::MEMBER_TYPE_ACCOMPLICE 	=> self::COUNTER_ACCOMPLICES_MUTED_EXPIRED,
		MemberTable::MEMBER_TYPE_AUDITOR 		=> self::COUNTER_AUDITOR_MUTED_EXPIRED
	];

	public const MAP_COMMENTS = [
		MemberTable::MEMBER_TYPE_RESPONSIBLE 	=> self::COUNTER_MY_NEW_COMMENTS,
		MemberTable::MEMBER_TYPE_ORIGINATOR 	=> self::COUNTER_ORIGINATOR_NEW_COMMENTS,
		MemberTable::MEMBER_TYPE_ACCOMPLICE 	=> self::COUNTER_ACCOMPLICES_NEW_COMMENTS,
		MemberTable::MEMBER_TYPE_AUDITOR 		=> self::COUNTER_AUDITOR_NEW_COMMENTS
	];

	public const MAP_MUTED_COMMENTS = [
		MemberTable::MEMBER_TYPE_RESPONSIBLE 	=> self::COUNTER_MY_MUTED_NEW_COMMENTS,
		MemberTable::MEMBER_TYPE_ORIGINATOR 	=> self::COUNTER_ORIGINATOR_MUTED_NEW_COMMENTS,
		MemberTable::MEMBER_TYPE_ACCOMPLICE 	=> self::COUNTER_ACCOMPLICES_MUTED_NEW_COMMENTS,
		MemberTable::MEMBER_TYPE_AUDITOR 		=> self::COUNTER_AUDITOR_MUTED_NEW_COMMENTS
	];

	public const MAP_COUNTERS = [
		self::COUNTER_EXPIRED => self::MAP_EXPIRED,
		self::COUNTER_NEW_COMMENTS => self::MAP_COMMENTS
	];

	/**
	 * @param string $name
	 * @return string
	 */
	public static function getCounterId(string $name): string
	{
		return self::PREFIX . $name;
	}
}
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
	public const PREFIX = 'tasks_';

	public const META_PROP_ALL = 'all';
	public const META_PROP_GROUP = 'group';
	public const META_PROP_PROJECT = 'project';
	public const META_PROP_SONET = 'sonet';
	public const META_PROP_SCRUM = 'scrum';
	public const META_PROP_NONE = 'none';

	public const LEFT_MENU_TASKS = 'tasks_total';

	public const
		COUNTER_TOTAL							= 'total',
		COUNTER_MEMBER_TOTAL					= 'member_total',

		COUNTER_NEW_COMMENTS					= 'new_comments',
		COUNTER_MUTED_NEW_COMMENTS				= 'muted_new_comments',
		COUNTER_EXPIRED							= 'expired',
		COUNTER_MUTED_EXPIRED					= 'muted_expired',
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

		COUNTER_GROUP_EXPIRED					= 'project_expired',
		COUNTER_GROUP_COMMENTS					= 'project_comments',

		COUNTER_PROJECTS_TOTAL_EXPIRED			= 'projects_total_expired',
		COUNTER_PROJECTS_TOTAL_COMMENTS			= 'projects_total_comments',
		COUNTER_PROJECTS_FOREIGN_EXPIRED		= 'projects_foreign_expired',
		COUNTER_PROJECTS_FOREIGN_COMMENTS		= 'projects_foreign_comments',
		COUNTER_PROJECTS_MAJOR					= 'projects_major',

		COUNTER_GROUPS_TOTAL_EXPIRED			= 'groups_total_expired',
		COUNTER_GROUPS_TOTAL_COMMENTS			= 'groups_total_comments',
		COUNTER_GROUPS_FOREIGN_EXPIRED			= 'groups_foreign_expired',
		COUNTER_GROUPS_FOREIGN_COMMENTS			= 'groups_foreign_comments',
		COUNTER_GROUPS_MAJOR					= 'groups_major',

		COUNTER_SONET_TOTAL_EXPIRED				= 'sonet_total_expired',
		COUNTER_SONET_TOTAL_COMMENTS			= 'sonet_total_comments',
		COUNTER_SONET_FOREIGN_EXPIRED			= 'sonet_foreign_expired',
		COUNTER_SONET_FOREIGN_COMMENTS			= 'sonet_foreign_comments',
		COUNTER_SONET_MAJOR						= 'sonet_major',

		COUNTER_SCRUM_TOTAL_COMMENTS			= 'scrum_total_comments',
		COUNTER_SCRUM_FOREIGN_COMMENTS			= 'scrum_foreign_comments',

		COUNTER_FLAG_COUNTED					= 'flag_computed_20210501',
		COUNTER_FLAG_CLEARED					= 'flag_cleared';

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

	public const MAP_SONET_TOTAL = [
		self::COUNTER_PROJECTS_TOTAL_EXPIRED => self::COUNTER_GROUPS_TOTAL_EXPIRED,
		self::COUNTER_PROJECTS_TOTAL_COMMENTS => self::COUNTER_GROUPS_TOTAL_COMMENTS,
		self::COUNTER_PROJECTS_FOREIGN_EXPIRED => self::COUNTER_GROUPS_FOREIGN_EXPIRED,
		self::COUNTER_PROJECTS_FOREIGN_COMMENTS => self::COUNTER_GROUPS_FOREIGN_COMMENTS,

		self::COUNTER_GROUPS_TOTAL_EXPIRED => self::COUNTER_GROUPS_TOTAL_EXPIRED,
		self::COUNTER_GROUPS_TOTAL_COMMENTS => self::COUNTER_GROUPS_TOTAL_COMMENTS,
		self::COUNTER_GROUPS_FOREIGN_EXPIRED => self::COUNTER_GROUPS_FOREIGN_EXPIRED,
		self::COUNTER_GROUPS_FOREIGN_COMMENTS => self::COUNTER_GROUPS_FOREIGN_COMMENTS,

		self::COUNTER_SONET_TOTAL_EXPIRED => self::COUNTER_GROUPS_TOTAL_EXPIRED,
		self::COUNTER_SONET_TOTAL_COMMENTS => self::COUNTER_GROUPS_TOTAL_COMMENTS,
		self::COUNTER_SONET_FOREIGN_EXPIRED => self::COUNTER_GROUPS_FOREIGN_EXPIRED,
		self::COUNTER_SONET_FOREIGN_COMMENTS => self::COUNTER_GROUPS_FOREIGN_COMMENTS,
	];

	public const MAP_SCRUM_TOTAL = [
		self::COUNTER_SCRUM_TOTAL_COMMENTS => self::COUNTER_GROUPS_TOTAL_COMMENTS,
		self::COUNTER_SCRUM_FOREIGN_COMMENTS => self::COUNTER_GROUPS_FOREIGN_COMMENTS,
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
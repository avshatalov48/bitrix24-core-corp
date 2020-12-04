<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Role;

use Bitrix\Tasks\Internals\Task\MemberTable;

class RoleDictionary extends \Bitrix\Main\Access\Role\RoleDictionary
{
	public const
		ROLE_DIRECTOR 		= MemberTable::MEMBER_TYPE_ORIGINATOR,
		ROLE_RESPONSIBLE 	= MemberTable::MEMBER_TYPE_RESPONSIBLE,
		ROLE_ACCOMPLICE 	= MemberTable::MEMBER_TYPE_ACCOMPLICE,
		ROLE_AUDITOR 		= MemberTable::MEMBER_TYPE_AUDITOR;

	public const
		TASKS_ROLE_ADMIN 	= 'TASKS_ROLE_ADMIN',
		TASKS_ROLE_CHIEF 	= 'TASKS_ROLE_CHIEF',
		TASKS_ROLE_MANAGER 	= 'TASKS_ROLE_MANAGER';
}
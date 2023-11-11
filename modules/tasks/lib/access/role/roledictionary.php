<?php
/**
 * Bitrix Framework
 *
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Role;

use Bitrix\Tasks\Internals\Task\MemberTable;

class RoleDictionary extends \Bitrix\Main\Access\Role\RoleDictionary
{
	public const ROLE_DIRECTOR = MemberTable::MEMBER_TYPE_ORIGINATOR;
	public const ROLE_RESPONSIBLE = MemberTable::MEMBER_TYPE_RESPONSIBLE;
	public const ROLE_ACCOMPLICE = MemberTable::MEMBER_TYPE_ACCOMPLICE;
	public const ROLE_AUDITOR = MemberTable::MEMBER_TYPE_AUDITOR;
	public const TASKS_ROLE_ADMIN = 'TASKS_ROLE_ADMIN';
	public const TASKS_ROLE_CHIEF = 'TASKS_ROLE_CHIEF';
	public const TASKS_ROLE_MANAGER = 'TASKS_ROLE_MANAGER';

	public static function getAvailableRoles(): array
	{
		return [
			static::ROLE_DIRECTOR,
			static::ROLE_RESPONSIBLE,
			static::ROLE_ACCOMPLICE,
			static::ROLE_AUDITOR,
		];
	}
}
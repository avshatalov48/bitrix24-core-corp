<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Role;

class RoleDictionary extends \Bitrix\Main\Access\Role\RoleDictionary
{
	public const
		ROLE_DIRECTOR 		= 'O',
		ROLE_RESPONSIBLE 	= 'R',
		ROLE_ACCOMPLICE 	= 'A',
		ROLE_AUDITOR 		= 'U';

	public const
		TASKS_ROLE_ADMIN 	= 'TASKS_ROLE_ADMIN',
		TASKS_ROLE_CHIEF 	= 'TASKS_ROLE_CHIEF',
		TASKS_ROLE_MANAGER 	= 'TASKS_ROLE_MANAGER';
}
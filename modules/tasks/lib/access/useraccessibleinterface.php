<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access;

use Bitrix\Main\Access\User\AccessibleUser;

interface UserAccessibleInterface
	extends AccessibleUser
{
	public function getPermission(string $permissionId, int $groupId = 0): ?int;
}
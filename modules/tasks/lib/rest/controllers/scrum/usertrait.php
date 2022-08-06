<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Rest\Controllers\Scrum;

use Bitrix\Main\UserTable;
use Bitrix\Tasks\Integration\SocialNetwork\Group;

trait UserTrait
{
	/**
	 * @param int $groupId
	 * @return bool
	 */
	private function checkAccess(int $groupId): bool
	{
		return Group::canReadGroupTasks($this->getUserId(), $groupId);
	}

	private function existsUser(int $userId): bool
	{
		$queryObject = UserTable::getList([
			'select' => ['ID'],
			'filter' => ['ID' => $userId]
		]);

		return (bool) $queryObject->fetch();
	}
}
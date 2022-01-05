<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Rest\Controllers\Scrum;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Tasks\Integration\SocialNetwork\Group;

trait UserTrait
{
	/**
	 * @return null
	 */
	private function getUserId()
	{
		return CurrentUser::get()->getId();
	}

	/**
	 * @param int $groupId
	 * @return bool
	 */
	private function checkAccess(int $groupId): bool
	{
		return Group::canReadGroupTasks($this->getUserId(), $groupId);
	}
}
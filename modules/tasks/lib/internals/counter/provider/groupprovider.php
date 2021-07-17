<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Counter\Provider;

use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\UserToGroupTable;

class GroupProvider
{
	private static $instance;
	private static $cache = [];

	/**
	 * @return GroupProvider
	 */
	public static function getInstance()
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct()
	{

	}

	/**
	 * @param array $groupIds
	 * @return array
	 */
	public function getGroupUsers(array $groupIds): array
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return [];
		}

		$groupIds = array_unique($groupIds);

		$this->loadInfo($groupIds);

		$res = [];
		foreach ($groupIds as $groupId)
		{
			$res[$groupId] = array_key_exists($groupId, self::$cache) ? self::$cache[$groupId]['USERS'] : [];
		}

		return $res;
	}

	/**
	 * @param array $groupIds
	 */
	private function loadInfo(array $groupIds)
	{
		$loadedGroups = array_keys(self::$cache);

		$groupIds = array_diff($groupIds, $loadedGroups);

		if (empty($groupIds))
		{
			return;
		}

		$minPermissions = $this->getMinPermissions($groupIds);

		foreach ($minPermissions as $groupId => $minRole)
		{
			$groupId = (int)$groupId;
			self::$cache[$groupId] = [
				'MIN_ROLE' => $minRole,
				'USERS' => $this->loadGroupUsers($groupId, $minRole)
			];
		}
	}

	/**
	 * @param int $groupId
	 * @param string $minRole
	 * @return array
	 */
	private function loadGroupUsers(int $groupId, string $minRole): array
	{
		$roles = $this->getAllowedRoles($minRole);

		$query = UserToGroupTable::query();
		$query->setSelect(['USER_ID']);
		$query->setFilter([
			'=GROUP_ID' => $groupId,
			'@ROLE' => $roles
		]);
		$res = $query->exec()->fetchCollection();

		$userIds = [];
		foreach ($res as $row)
		{
			$userIds[] = (int)$row['USER_ID'];
		}

		return $userIds;
	}

	/**
	 * @param string $minRole
	 * @return array
	 */
	private function getAllowedRoles(string $minRole): array
	{
		$rolesList = UserToGroupTable::getRolesAll();

		$res = [];
		foreach ($rolesList as $role)
		{
			$res[] = $role;
			if ($role === $minRole)
			{
				return $res;
			}
		}

		return [];
	}

	/**
	 * @param array $groupIds
	 * @return array
	 */
	private function getMinPermissions(array $groupIds): array
	{
		return \CSocNetFeaturesPerms::GetOperationPerm(SONET_ENTITY_GROUP, $groupIds, 'tasks', 'view_all');
	}
}
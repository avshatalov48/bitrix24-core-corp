<?php
/**
 * @access private
 */

namespace Bitrix\Tasks\Integration\SocialNetwork;

use Bitrix\Main;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Query\Filter;
use Bitrix\Main\Search\Content;
use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Socialnetwork\Helper\Workgroup;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Util\User;

class Group extends \Bitrix\Tasks\Integration\SocialNetwork
{
	const ACTION_VIEW_OWN_TASKS = 'view';
	const ACTION_VIEW_ALL_TASKS = 'view_all';
	const ACTION_CREATE_TASKS = 'create_tasks';
	const ACTION_EDIT_TASKS = 'edit_tasks';
	const ACTION_DELETE_TASKS = 'delete_tasks';
	const ACTION_SORT_TASKS = 'sort';

	/**
	 * @var array
	 * @access private
	 */
	static $cache = array();

	public static function clearCaches()
	{
		static::$cache = array();
	}

	public static function can($id, $actionCode, $userId = 0)
	{
		if(!static::includeModule())
		{
			return false; // no module = what groups?
		}

		if(!$userId)
		{
			$userId = User::getId();
		}

		return \CSocNetFeaturesPerms::canPerformOperation(
			$userId, SONET_ENTITY_GROUP,
			$id, 'tasks', $actionCode
		);
	}

	public static function getIdsByAllowedAction($actionCode = self::ACTION_VIEW_ALL_TASKS, $ownGroups = true, $userId = 0)
	{
		if (!static::includeModule())
		{
			return [];
		}

		$cacheKey = $actionCode.'-'.$userId;

		if(array_key_exists($cacheKey, static::$cache))
		{
			return static::$cache[$cacheKey];
		}

		$groups = \Bitrix\Socialnetwork\Item\Workgroup::getByFeatureOperation([
			'feature' 	=> 'tasks',
			'operation' => $actionCode,
			'userId' 	=> $userId
		]);

		$groups = array_column($groups, 'ID');

		static::$cache[$cacheKey] = $groups;

		return static::$cache[$cacheKey];
	}

	/**
	 * @param array $groupIds
	 * @param array $select
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getData(array $groupIds, array $select = [], array $params = []): array
	{
		$groupIds = array_unique(array_filter($groupIds, 'intval'));

		if (empty($groupIds) || !static::includeModule())
		{
			return [];
		}

		$defaultSelect = ['ID', 'NAME'];
		$parameters = [
			'select' => (empty($select) ? $defaultSelect : array_merge($defaultSelect, $select)),
			'filter' => ['ID' => $groupIds],
		];
		$expanded = (User::getOption('opened_projects') ?: []);

		// todo: make static caches here
		$groups = [];
		$groupResult = WorkgroupTable::getList($parameters);
		while ($group = $groupResult->fetch())
		{
			$groupId = $group['ID'];
			$group['EXPANDED'] = !(array_key_exists($groupId, $expanded) && $expanded[$groupId] === "false");
			$groups[$groupId] = $group;
		}

		if (
			!empty($groups)
			&& isset($params['MODE'])
			&& mb_strtolower($params['MODE']) === 'mobile'
		)
		{
			$additionalData = Workgroup::getAdditionalData([
				'ids' => array_keys($groups),
				'features' => \Bitrix\Mobile\Project\Helper::getMobileFeatures(),
				'mandatoryFeatures' => \Bitrix\Mobile\Project\Helper::getMobileMandatoryFeatures(),
				'currentUserId' => (int)($params['CURRENT_USER_ID'] ?? User::getId()),
			]);

			foreach (array_keys($groups) as $id)
			{
				if (!isset($additionalData[$id]))
				{
					continue;
				}

				$groups[$id]['ADDITIONAL_DATA'] = ($additionalData[$id] ?? []) ;
			}
		}
		return $groups;
	}

	/**
	 * @param string $searchText
	 * @param array $select
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function searchGroups(string $searchText, array $select = []): array
	{
		$defaultSelect = ['ID', 'NAME'];
		$searchText = Filter\Helper::matchAgainstWildcard(Content::prepareStringToken($searchText));

		if ($searchText === '')
		{
			return [];
		}

		$query = WorkgroupTable::query()
			->setSelect((empty($select) ? $defaultSelect : array_merge($defaultSelect, $select)))
			->whereMatch('SEARCH_INDEX', $searchText)
		;

		return $query->exec()->fetchAll();
	}

	public static function updateLastActivity($id)
	{
		$id = intval($id);

		if(!static::includeModule() || !$id)
		{
			return false;
		}

		\CSocNetGroup::setLastActivity($id);

		return true;
	}

	public static function extractPublicData(array $group)
	{
		$safe = array(
			'NAME' => $group['NAME'],
		);

		if(intval($group['ID']))
		{
			$safe['ID'] = intval($group['ID']);
		}

		return $safe;
	}

	public static function getLastViewedProject($userId): int
	{
		$res = \Bitrix\Socialnetwork\WorkgroupViewTable::getList(array(
			'select' => array(
				'GROUP_ID'
			),
			'filter' => array(
				'USER_ID' => $userId,
				'=GROUP.ACTIVE' => 'Y',
				'=GROUP.CLOSED' => 'N',
			),
			'order' => array(
				'DATE_VIEW' => 'DESC'
			)
		));
		while ($row = $res->fetch())
		{
			if (self::canReadGroupTasks($userId, $row['GROUP_ID']))
			{
				$lastGroupId = $row['GROUP_ID'];
				break;
			}
		}
		if (!$lastGroupId)
		{
			// get by date activity
			$res = \CSocNetUserToGroup::GetList(
				array(
					'GROUP_DATE_ACTIVITY' => 'DESC'
				),
				array(
					'USER_ID' => $userId,
					'!ROLE' => array(
						SONET_ROLES_BAN,
						SONET_ROLES_REQUEST
					),
					'USER_ACTIVE' => 'Y',
					'GROUP_ACTIVE' => 'Y'
				),
				false, false,
				array(
					'GROUP_ID'
				)
			);
			while ($row = $res->fetch())
			{
				if (self::canReadGroupTasks($userId, $row['GROUP_ID']))
				{
					$lastGroupId = $row['GROUP_ID'];
					break;
				}
			}
		}
		return (int) $lastGroupId;
	}

	public static function setLastViewedProject($userId, int $groupId)
	{
		if (!$groupId)
		{
			return true;
		}

		\Bitrix\Socialnetwork\WorkgroupViewTable::set(array(
			'GROUP_ID' => $groupId,
			'USER_ID' => $userId
		));
	}

	public static function canReadGroupTasks($userId, $groupId)
	{
		static $access = [];

		if (
			array_key_exists($userId, $access)
			&& array_key_exists($groupId, $access[$userId])
		)
		{
			return $access[$userId][$groupId];
		}

		$activeFeatures = \CSocNetFeatures::GetActiveFeaturesNames(SONET_ENTITY_GROUP, $groupId);
		if (!is_array($activeFeatures) || !array_key_exists('tasks', $activeFeatures))
		{
			$access[$userId][$groupId] = false;
			return $access[$userId][$groupId];
		}

		$featurePerms = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, array($groupId), 'tasks', 'view_all');
		$bCanViewGroup = is_array($featurePerms) && isset($featurePerms[$groupId]) && $featurePerms[$groupId];
		if (!$bCanViewGroup)
		{
			$featurePerms = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, array($groupId), 'tasks', 'view');
			$bCanViewGroup = is_array($featurePerms) && isset($featurePerms[$groupId]) && $featurePerms[$groupId];
		}

		$access[$userId][$groupId] = $bCanViewGroup;

		return $access[$userId][$groupId];
	}

	public static function delete(array $groupIds): array
	{
		global $APPLICATION;

		$result = [];

		if (empty($groupIds) || !static::includeModule())
		{
			return $result;
		}

		$permissions = static::checkPermissions($groupIds);
		foreach ($permissions as $groupId => $canDelete)
		{
			$APPLICATION->ResetException();

			$deleteResult = ($canDelete && \CSocNetGroup::Delete($groupId));
			if (!$deleteResult && ($e = $APPLICATION->GetException()))
			{
				$deleteResult = $e->GetString();
			}

			$result[$groupId] = $deleteResult;
		}

		return $result;
	}

	public static function update(array $groupIds, array $data): array
	{
		$result = [];

		if (empty($groupIds) || !static::includeModule())
		{
			return $result;
		}

		$permissions = static::checkPermissions($groupIds);
		foreach ($permissions as $groupId => $canUpdate)
		{
			$result[$groupId] = ($canUpdate ? \CSocNetGroup::Update($groupId, $data) : false);
		}

		return $result;
	}

	public static function addToArchive(array $groupIds): array
	{
		$result = [];

		if (empty($groupIds) || !static::includeModule())
		{
			return $result;
		}

		$permissions = static::checkPermissions($groupIds);
		foreach ($permissions as $groupId => $canUpdate)
		{
			$result[$groupId] = ($canUpdate ? \CSocNetGroup::Update($groupId, ['CLOSED' => 'Y']) : false);
		}

		return $result;
	}

	public static function removeFromArchive(array $groupIds): array
	{
		$result = [];

		if (empty($groupIds) || !static::includeModule())
		{
			return $result;
		}

		$permissions = static::checkPermissions($groupIds);
		foreach ($permissions as $groupId => $canUpdate)
		{
			$result[$groupId] = ($canUpdate ? \CSocNetGroup::Update($groupId, ['CLOSED' => 'N']) : false);
		}

		return $result;
	}

	public static function checkPermissions(array $groupIds): array
	{
		$permissions = array_fill_keys($groupIds, false);

		$userId = User::getId();
		$isAdmin = \CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false);

		$filter = ['@ID' => $groupIds];
		if (!$isAdmin)
		{
			$filter['CHECK_PERMISSIONS'] = $userId;
		}

		$dbRes = \CSocNetGroup::GetList([], $filter, false, false, ['ID', 'OWNER_ID']);
		while ($group = $dbRes->Fetch())
		{
			$permissions[$group['ID']] = ($isAdmin || (int)$group['OWNER_ID'] === $userId);
		}

		return $permissions;
	}

	public static function getGroupLastActivityDate(int $groupId): ?Main\Type\DateTime
	{
		if (!static::includeModule())
		{
			return null;
		}

		$query = WorkgroupTable::query()
			->setSelect([
				'DATE_CREATE',
				new ExpressionField('ACTIVITY_DATE', 'MAX(%1$s)', ['T.ACTIVITY_DATE']),
			])
			->registerRuntimeField(
				'T',
				new ReferenceField(
					'T',
					TaskTable::getEntity(),
					Join::on('this.ID', 'ref.GROUP_ID'),
					['join_type' => 'left']
				)
			)
			->where('ID', $groupId)
		;

		$lastActivityDate = null;

		$res = $query->exec();
		if ($row = $res->fetch())
		{
			$lastActivityDate = ($row['ACTIVITY_DATE'] ?? $row['DATE_CREATE']);
		}

		return $lastActivityDate;
	}

	public static function getUserPermissionsInGroup(int $groupId, int $userId = 0): array
	{
		if (!static::includeModule())
		{
			return [];
		}

		$userId = ($userId ?: User::getId());

		return Workgroup::getPermissions([
			'groupId' => $groupId,
			'userId' => $userId,
		]);
	}

	/**
	 * @param int $userIdA
	 * @param int $userIdB
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function usersHasCommonGroup(int $userIdA, int $userIdB): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		global $DB;

		$sql = '
			SELECT count(*) as cnt
			FROM b_sonet_user2group ug
			INNER JOIN b_sonet_user2group ug2
				ON ug.GROUP_ID = ug2.GROUP_ID 
				AND ug2.USER_ID = '. $userIdB .'
				AND ug2.ROLE IN ("'. implode('","', \Bitrix\Socialnetwork\UserToGroupTable::getRolesMember()) .'")
			WHERE 
				ug.USER_ID = '. $userIdA .'
				AND ug.ROLE IN ("'. implode('","', \Bitrix\Socialnetwork\UserToGroupTable::getRolesMember()) .'")
		';

		$res = $DB->query($sql);
		$row = $res->fetch();
		if ($row && (int) $row['cnt'] > 0)
		{
			return true;
		}

		return false;
	}
}
<?
/**
 * @access private
 */

namespace Bitrix\Tasks\Integration\SocialNetwork;

use Bitrix\Main\Entity;

use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Util\User as UtilUser;

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
			$userId = UtilUser::getId();
		}

		return \CSocNetFeaturesPerms::canPerformOperation(
			$userId, SONET_ENTITY_GROUP,
			$id, 'tasks', $actionCode
		);
	}

	public static function getIdsByAllowedAction($actionCode = 'view_all', $ownGroups = true, $userId = 0)
	{
		$result = array();

		if(static::includeModule())
		{
			$cacheKey = $actionCode.'-'.$ownGroups.'-'.$userId;

			if(array_key_exists($cacheKey, static::$cache))
			{
				return static::$cache[$cacheKey];
			}

			$runtime = array();
			if($ownGroups) // select only groups that are used in tasks
			{
				$runtime = array(new Entity\ReferenceField(
					'TASKS',
					TaskTable::getEntity(),
					array(
						'=ref.GROUP_ID' => 'this.ID',
						// todo: get rid of zombie tasks, make an additional table to keep deleted tasks for MSExchange
						'=ref.ZOMBIE' => array('?', 'N'),
					),
					array('join_type' => 'inner')
				));
			}

			$res = WorkgroupTable::getList(array(
				'runtime' => $runtime,
				'filter' => array(
					'=ACTIVE' => 'Y'
				),
				'select' => array(
					'ID'
				),
				'group' => array(
				    'ID'
				)
			));
			$groupIds = array();
			while($item = $res->fetch())
			{
				$groupIds[] = $item['ID'];
			}

			if(!empty($groupIds))
			{
				$access = static::can($groupIds, $actionCode, $userId);
				if(is_array($access))
				{
					foreach($access as $id => $can)
					{
						if($can)
						{
							$result[] = intval($id);
						}
					}
				}

				static::$cache[$cacheKey] = $result;
			}
		}

		return $result;
	}

	public static function getData(array $groupIds)
	{
		if(!static::includeModule())
		{
			return array(); // no module = no groups
		}

		$groupIds = array_unique(array_filter($groupIds, 'intval'));
		if(empty($groupIds))
		{
			return array(); // which groups?
		}

		$expanded = UtilUser::getOption('opened_projects');
		if(!$expanded)
		{
			$expanded = array();
		}

		$result = array();
		// todo: make static caches here
		$res = WorkgroupTable::getList(array('filter' => array('ID' => $groupIds)));
		while($item = $res->fetch())
		{
			$item['EXPANDED'] = array_key_exists($item["ID"], $expanded) && $expanded[$item["ID"]] == "false" ? false : true;
			$result[$item['ID']] = $item;
		}

		return $result;
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
				'=GROUP.CLOSED' => 'N'
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
}
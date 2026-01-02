<?php

namespace Bitrix\BIConnector\Access\Rule;

use Bitrix\BIConnector\Access\Permission\PermissionDictionary;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;

class VariableRule extends BaseRule
{
	public function getPermissionMultiValues(array $params): ?array
	{
		$actionPermissionCode = (int)static::getPermissionCode($params);
		$groups = SupersetDashboardGroupTable::getList([
			'select' => ['ID', 'OWNER_ID'],
			'cache' => ['ttl' => 3600],
		]);

		$allowedGroupIds = [];

		if ($this->isAbleToSkipChecking())
		{
			return array_column($groups->fetchAll(), 'ID');
		}
		$userId = $this->user->getUserId();

		while ($group = $groups->fetch())
		{
			if ((int)$group['OWNER_ID'] === $userId)
			{
				$allowedGroupIds[] = $group['ID'];

				continue;
			}

			$groupId = $group['ID'];
			$groupPermissionId = PermissionDictionary::getDashboardGroupPermissionId($groupId);
			$groupActions = $this->user->getPermissionMulti($groupPermissionId);
			if (empty($groupActions))
			{
				continue;
			}

			if (
				$groupActions[0] === PermissionDictionary::VALUE_VARIATION_ALL
				|| in_array($actionPermissionCode, $groupActions, true)
			)
			{
				$allowedGroupIds[] = $groupId;
			}
		}

		return !empty($allowedGroupIds) ? $allowedGroupIds : null;
	}

	public function getPermissionAllowedDashboardIds(array $params): array
	{
		if ($this->isAbleToSkipChecking())
		{
			$dashboards = SupersetDashboardTable::getList([
				'select' => ['ID'],
				'cache' => ['ttl' => 3600],
			])
				->fetchAll()
			;

			return array_column($dashboards, 'ID');
		}

		$allowedGroupIds = $this->getPermissionAllowedGroupIds($params);

		return $this->loadGroupDashboards($allowedGroupIds);
	}

	public function getPermissionAllowedGroupIds(array $params): array
	{
		return $this->getPermissionMultiValues($params) ?? [];
	}

	protected function loadGroupDashboards(array $groupIds): array
	{
		if (empty($groupIds))
		{
			$filter = ['OWNER_ID' => $this->user->getUserId()];
		}
		else
		{
			$filter = [
				'LOGIC' => 'OR',
				'GROUPS.ID' => $groupIds,
				'OWNER_ID' => $this->user->getUserId(),
			];
		}

		$dashboardList = SupersetDashboardTable::getList([
			'select' => ['ID'],
			'filter' => $filter,
			'cache' => ['ttl' => 3600],
		])
			->fetchAll()
		;

		return array_unique(
			array_column($dashboardList, 'ID'),
		);
	}

	public function check(array $params): bool
	{
		$values = $this->getPermissionAllowedDashboardIds($params);
		if (!$values)
		{
			return false;
		}

		if (!isset($params['value']) && !empty($values))
		{
			return true;
		}

		$checkDashboardIds = (array)($params['value'] ?? []);

		return empty(array_diff($checkDashboardIds, $values));
	}
}

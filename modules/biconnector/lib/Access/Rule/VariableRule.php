<?php

namespace Bitrix\BIConnector\Access\Rule;

use Bitrix\BIConnector\Access\Permission\PermissionDictionary;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;

class VariableRule extends BaseRule
{
	public function getPermissionMultiValues(array $params): ?array
	{
		$permissionCode = static::getPermissionCode($params);
		$values = $this->user->getPermissionMulti($permissionCode);

		return $values ? array_intersect($values, $this->getAvailableValues()) : null;
	}

	protected function getAvailableValues(): array
	{
		$values = $this->loadAvailableValues();
		$values[] = $this->getAllValue();

		return $values;
	}

	protected function loadAvailableValues(): array
	{
		$dashboardList = SupersetDashboardTable::getList([
			'select' => ['ID'],
			'cache' => ['ttl' => 3600],
		])->fetchAll();

		return array_column($dashboardList, 'ID');
	}

	protected function getAllValue(): int
	{
		return PermissionDictionary::VALUE_VARIATION_ALL;
	}

	public function check(array $params): bool
	{
		$values = $this->getPermissionMultiValues($params);
		if (!$values)
		{
			return false;
		}

		if (
			(!isset($params['value']) && !empty($values))
			|| in_array($this->getAllValue(), $values, true)
		)
		{
			return true;
		}

		$checkDashboardIds = (array)($params['value'] ?? []);

		return empty(array_diff($checkDashboardIds, $values));
	}
}

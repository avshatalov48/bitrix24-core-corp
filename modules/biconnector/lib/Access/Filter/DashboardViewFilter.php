<?php

namespace Bitrix\BIConnector\Access\Filter;

use Bitrix\BIConnector\Access\Model\DashboardAccessItem;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\Main\Access\Filter\AbstractAccessFilter;

class DashboardViewFilter extends AbstractAccessFilter
{
	/**
	 * Filter for dashboards.
	 *
	 * @param string $entity ORM entity (table class name) to check values from.
	 * @param array $params Additional filter params. Contains 'action' string from ActionDictionary.
	 *
	 * @return array ORM filter for SupersetDashboardTable.
	 */
	public function getFilter(string $entity, array $params = []): array
	{
		$action = (string)($params['action'] ?? '');
		if (empty($action))
		{
			return ['=ID' => null];
		}

		if ($this->user->isAdmin())
		{
			return [];
		}

		if ($entity === SupersetDashboardTable::class)
		{
			$dashboards = SupersetDashboardTable::getList([
				'select' => ['ID', 'TYPE', 'OWNER_ID'],
				'filter' => [
					'LOGIC' => 'OR',
					'!STATUS' => SupersetDashboardTable::DASHBOARD_STATUS_DRAFT,
					[
						'STATUS' => SupersetDashboardTable::DASHBOARD_STATUS_DRAFT,
						'OWNER_ID' => $this->user->getUserId(),
					]
				],
				'cache' => ['ttl' => 3600],
			])->fetchAll();
			$allowedDashboardIds = array_filter(
				$dashboards,
				fn(array $dashboard) => $this->controller->check($action, DashboardAccessItem::createFromArray([
					'ID' => $dashboard['ID'],
					'TYPE' => $dashboard['TYPE'],
					'OWNER_ID' => $dashboard['OWNER_ID'],
				]))
			);
			$allowedDashboardIds = array_column($allowedDashboardIds, 'ID');

			if (empty($allowedDashboardIds))
			{
				return ['=ID' => null];
			}

			return [
				'=ID' => $allowedDashboardIds,
			];
		}

		return ['=ID' => null];
	}
}

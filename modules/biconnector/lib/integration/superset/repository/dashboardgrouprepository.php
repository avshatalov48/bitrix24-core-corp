<?php

namespace Bitrix\BIConnector\Integration\Superset\Repository;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Integration\Superset\Model\Dashboard;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupBindingTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTagTable;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\BIConnector\Configuration\DashboardTariffConfigurator;
use Bitrix\BIConnector\Superset\Dashboard\UrlParameter;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\Entity\Base;

final class DashboardGroupRepository extends DashboardRepository
{
	public const TYPE_DASHBOARD = 'D';
	public const TYPE_GROUP = 'G';

	/**
	 * Load data from DB using additional data from proxy
	 *
	 * @param array $ormParams
	 * @param bool $needLoadProxyData
	 *
	 * @return Dashboard[]
	 */
	public function getList(array $ormParams, bool $needLoadProxyData = false): array
	{
		$query = $this->prepareUnionQuery($ormParams);

		$ormParams['order'] ??= [];
		$ormParams['order'] = array_merge(['ENTITY_TYPE' => 'desc'], $ormParams['order']);

		$query->setUnionOrder($ormParams['order']);
		$items = $query->exec();

		$rows = [];
		$dashboardIds = [];
		$groupIds = [];
		while ($item = $items->fetch())
		{
			$itemId = $item['ID'];
			$entityType = $item['ENTITY_TYPE'];
			$rows["{$itemId}_{$entityType}"] = [];
			if ($entityType === self::TYPE_GROUP)
			{
				$groupIds[] = $itemId;
			}
			else
			{
				$dashboardIds[] = $itemId;
			}
		}

		if (!empty($dashboardIds))
		{
			$dashboards = $this->getDashboardRows($dashboardIds, $needLoadProxyData);
			foreach ($dashboards as $dashboard)
			{
				$rows[$dashboard['ID'] . '_' . self::TYPE_DASHBOARD] = $dashboard;
			}
		}

		if (!empty($groupIds))
		{
			$groups = $this->getGroupRows($groupIds);
			foreach ($groups as $group)
			{
				$rows[$group['ID'] . '_' . self::TYPE_GROUP] = $group;
			}
		}

		return array_values($rows);
	}

	public function getCount(array $ormParams): int
	{
		unset($ormParams['order']);
		$query =
			$this
				->prepareUnionQuery($ormParams)
				->countTotal(true)
		;

		return $query->exec()->getCount();
	}

	public function getDashboardRow(
		Dashboard $dashboard,
		array $additionalOptions = [],
	): array
	{
		$row = $dashboard->toArray();
		$urlService = new UrlParameter\Service($dashboard->getOrmObject());
		$row['URL_PARAMS'] = [];
		foreach ($urlService->getUrlParameters() as $parameter)
		{
			$row['URL_PARAMS'][] = $parameter->title();
		}
		$row['DETAIL_URL'] = $urlService->getEmbeddedUrl();
		if (!$dashboard->getOrmObject()->isTagsFilled())
		{
			$dashboard->getOrmObject()->fillTags();
		}
		$row['TAGS'] = $dashboard->getOrmObject()->getTags()->collectValues();

		if (!$dashboard->getOrmObject()->isGroupsFilled())
		{
			$dashboard->getOrmObject()->fillGroups();
		}
		$row['GROUPS'] = $dashboard->getOrmObject()->getGroups()->collectValues();

		if (!$dashboard->getOrmObject()->isScopeFilled())
		{
			$dashboard->getOrmObject()->fillScope();
		}
		$row['SCOPE'] = $dashboard->getOrmObject()->getScope()->getScopeCodeList();
		sort($row['SCOPE']);

		$appId = $dashboard->getAppId();
		$row['IS_TARIFF_RESTRICTED'] = !empty($appId) && !DashboardTariffConfigurator::isAvailableDashboard($appId);
		$row['IS_AVAILABLE_DASHBOARD'] = $dashboard->isAvailableDashboard();
		if ($dashboard->getOrmObject()->getSource())
		{
			$urlSourceService = new UrlParameter\Service($dashboard->getOrmObject()->getSource());
			$row['SOURCE_DETAIL_URL'] = $urlSourceService->getEmbeddedUrl();
		}
		$row['ENTITY_TYPE'] = self::TYPE_DASHBOARD;

		return array_merge($row, $additionalOptions);
	}

	private function getDashboardRows(array $ids, bool $needLoadProxyData = false): array
	{
		$dashboardOrmParams = [
			'filter' => ['ID' => $ids],
		];

		$dashboardsInTopMenu = \CUserOptions::getOption('biconnector', 'top_menu_dashboards', []);
		$dashboardsInTopMenu = array_flip(array_intersect($ids, $dashboardsInTopMenu));
		$pinnedDashboards = \CUserOptions::getOption('biconnector', 'grid_pinned_dashboards', []);
		$pinnedDashboards = array_flip(array_intersect($ids, $pinnedDashboards));

		$allowedIds = AccessController::getCurrent()->getAllowedDashboardValue(
			ActionDictionary::ACTION_BIC_DASHBOARD_VIEW,
		);
		$allowedIds = array_map('intval', $allowedIds);

		$dashboardRows = [];
		$dashboards = parent::getList($dashboardOrmParams, $needLoadProxyData);
		foreach ($dashboards as $dashboard)
		{
			$dashboardRows[] = $this->getDashboardRow(
				$dashboard,
				[
					'IS_IN_TOP_MENU' => isset($dashboardsInTopMenu[$dashboard->getId()]),
					'IS_PINNED' => isset($pinnedDashboards[$dashboard->getId()]),
					'IS_ACCESS_ALLOWED' => in_array($dashboard->getId(), $allowedIds, true),
				],
			);
		}

		return  $dashboardRows;
	}

	private function getGroupRows(array $ids): array
	{
		$allowedIds = AccessController::getCurrent()->getAllowedGroupValue(
			ActionDictionary::ACTION_BIC_DASHBOARD_VIEW,
		);
		$allowedIds = array_map('intval', $allowedIds);

		$groupOrmParams = [
			'filter' => ['ID' => $ids],
			'select' => ['*', 'SCOPE', 'DASHBOARDS.ID'],
		];

		$groupRows = [];
		$emptyCommonColumns = [
			"EXTERNAL_ID",
			"STATUS",
			"URL",
			"DETAIL_URL",
			"EDIT_URL",
			"SOURCE_ID",
			"SOURCE_TITLE",
			"APP_ID",
			"CREATED_BY_ID",
			"FILTER_PERIOD",
			"DATE_FILTER_START",
			"DATE_FILTER_END",
			"INCLUDE_LAST_FILTER_DATE",
			"IS_TARIFF_RESTRICTED",
			"IS_AVAILABLE_DASHBOARD",
		];

		$groups = SupersetDashboardGroupTable::getList($groupOrmParams)->fetchCollection();
		foreach ($groups as $group)
		{
			$row = [
				"ID" => $group->getId(),
				"TITLE" => $group->getName(),
				"DATE_CREATE" => $group->getDateCreate(),
				"DATE_MODIFY" => $group->getDateModify(),
				"SCOPE" => $group->getScope()->getScopeCodeList(),
				"TYPE" => $group->getType(),
				"TAGS" => [],
				"IS_ACCESS_ALLOWED" => in_array($group->getId(), $allowedIds, true),
				"URL_PARAMS" => [],
				"GROUPS" => [],
				'OWNER_ID' => $group->getOwnerId(),
				'ENTITY_TYPE' => self::TYPE_GROUP,
				'COUNT_DASHBOARDS' => $group->getDashboards()->count(),
			];

			sort($row['SCOPE']);
			foreach ($emptyCommonColumns as $column)
			{
				$row[$column] = '';
			}

			$groupRows[] = $row;
		}

		return $groupRows;
	}

	/**
	 * Method `GetById` is not allowed
	 *
	 * @param int $dashboardId
	 * @param bool $needLoadProxyData
	 *
	 * @return Dashboard|null
	 */
	public function getById(int $dashboardId, bool $needLoadProxyData = false): ?Dashboard
	{
		return null;
	}

	private function prepareUnionQuery(array $ormParams): Query
	{
		$queryDashboard = SupersetDashboardTable::query()
			->addSelect(new ExpressionField('ENTITY_TYPE', "'" . self::TYPE_DASHBOARD . "'"))
			->addSelect('ID')
		;

		$emptyUnionFilterGroupFields = [
			'SOURCE_ID',
			'STATUS',
			'TAGS',
			'GROUPS',
			'URL_PARAMS',
			'CREATED_BY_ID',
			'FILTER_PERIOD',
		];

		$queryGroup = SupersetDashboardGroupTable::query()
			->addSelect(new ExpressionField('ENTITY_TYPE', "'" . self::TYPE_GROUP . "'"))
			->addSelect('ID')
			->registerRuntimeField(new ExpressionField('TITLE', 'NAME'))
		;

		foreach ($emptyUnionFilterGroupFields as $unionField)
		{
			$queryGroup->registerRuntimeField(new ExpressionField($unionField, 'NULL'));
		}

		if (!empty($ormParams['runtime']) && is_array($ormParams['runtime']))
		{
			foreach ($ormParams['runtime'] as $runtimeField)
			{
				if ($runtimeField->getName() === 'IS_PINNED')
				{
					$queryGroup->registerRuntimeField(new ExpressionField($runtimeField->getName(), 0));
					$queryGroup->addSelect($runtimeField->getName());
					$queryDashboard->registerRuntimeField($runtimeField);
					$queryDashboard->addSelect($runtimeField->getName());

					break;
				}
			}
		}

		if (!empty($ormParams['order']) && is_array($ormParams['order']))
		{
			foreach (array_keys($ormParams['order']) as $fieldCode)
			{
				if ($fieldCode !== 'ENTITY_TYPE' && $fieldCode !== 'ID')
				{
					$queryGroup->addSelect($fieldCode);
					$queryDashboard->addSelect($fieldCode);
				}
			}
		}

		if (!empty($ormParams['filter']))
		{
			$dashboardFilter = $ormParams['filter'];
			if (array_key_exists('DASHBOARD_ID', $dashboardFilter))
			{
				$dashboardFilter['=ID'] = $dashboardFilter['DASHBOARD_ID'];
			}

			unset($dashboardFilter['DASHBOARD_ID']);
			unset($dashboardFilter['GROUP_ID']);

			$groupFilter = $ormParams['filter'];
			if (array_key_exists('GROUP_ID', $groupFilter))
			{
				$groupFilter['=ID'] = $groupFilter['GROUP_ID'];
			}
			unset($groupFilter['DASHBOARD_ID']);
			unset($groupFilter['GROUP_ID']);

			$queryDashboard->setFilter($dashboardFilter);

			//** The group query is unuseful by group id or tag id filtering **/
			if (isset($ormParams['filter']['GROUPS.ID']) || isset($ormParams['filter']['TAGS.ID']))
			{
				if (!empty($ormParams['limit']) && (int)$ormParams['limit'] > 0)
				{
					$queryDashboard->setLimit((int)$ormParams['limit']);
				}

				if (!empty($ormParams['offset']) && (int)$ormParams['offset'] > 0)
				{
					$queryDashboard->setOffset((int)$ormParams['offset']);
				}

				if (
					!empty($ormParams['filter']['GROUPS.ID'])
					&& is_array($ormParams['filter']['GROUPS.ID'])
					&& count($ormParams['filter']['GROUPS.ID']) > 1
				)
				{
					$subQuery = SupersetDashboardGroupBindingTable::query()
						->setSelect(['DASHBOARD_ID', 'COUNT'])
						->registerRuntimeField('COUNT', new ExpressionField('COUNT', 'COUNT(1)'))
						->whereIn('GROUP_ID', $ormParams['filter']['GROUPS.ID'])
						->addGroup('DASHBOARD_ID')
					;

					$queryDashboard->registerRuntimeField(
						new Reference(
							'SUBQUERY_COUNT_GROUP',
							Base::getInstanceByQuery($subQuery),
							['this.ID' => 'ref.DASHBOARD_ID'],
							['join_type' => 'INNER'],
						),
					);

					$queryDashboard->addFilter('SUBQUERY_COUNT_GROUP.COUNT', count($ormParams['filter']['GROUPS.ID']));
				}

				if (
					!empty($ormParams['filter']['TAGS.ID'])
					&& is_array($ormParams['filter']['TAGS.ID'])
					&& count($ormParams['filter']['TAGS.ID']) > 1
				)
				{
					$subQuery = SupersetDashboardTagTable::query()
						->setSelect(['DASHBOARD_ID', 'COUNT'])
						->registerRuntimeField('COUNT', new ExpressionField('COUNT', 'COUNT(1)'))
						->whereIn('TAG_ID', $ormParams['filter']['TAGS.ID'])
						->addGroup('DASHBOARD_ID')
					;

					$queryDashboard->registerRuntimeField(
						new Reference(
							'SUBQUERY_TAG_GROUP',
							Base::getInstanceByQuery($subQuery),
							['this.ID' => 'ref.DASHBOARD_ID'],
							['join_type' => 'INNER'],
						),
					);

					$queryDashboard->addFilter('SUBQUERY_TAG_GROUP.COUNT', count($ormParams['filter']['TAGS.ID']));
				}

				return $queryDashboard;
			}

			$queryGroup->setFilter($groupFilter);
		}

		$queryGroup->unionAll($queryDashboard);

		if (!empty($ormParams['limit']) && (int)$ormParams['limit'] > 0)
		{
			$queryGroup->setUnionLimit((int)$ormParams['limit']);
		}

		if (!empty($ormParams['offset']) && (int)$ormParams['offset'] > 0)
		{
			$queryGroup->setUnionOffset((int)$ormParams['offset']);
		}

		return $queryGroup;
	}
}

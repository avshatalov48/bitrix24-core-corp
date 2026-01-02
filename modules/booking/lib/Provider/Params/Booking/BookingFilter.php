<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider\Params\Booking;

use Bitrix\Booking\Entity\Booking\BookingVisitStatus;
use Bitrix\Booking\Entity\ExternalData\ExternalDataItem;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Model\BookingClientTable;
use Bitrix\Booking\Internals\Model\BookingResourceTable;
use Bitrix\Booking\Internals\Model\BookingTable;
use Bitrix\Booking\Internals\Model\ClientTypeTable;
use Bitrix\Booking\Internals\Model\Enum\EntityType;
use Bitrix\Booking\Internals\Model\ScorerTable;
use Bitrix\Booking\Internals\Service\CounterDictionary;
use Bitrix\Booking\Internals\Service\Time;
use Bitrix\Booking\Provider\Params\Filter;
use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;

class BookingFilter extends Filter
{
	private array $filter;
	private string $initAlias;
	private int $currentTimestamp;
	private Connection $connection;

	public function __construct(array $filter = [])
	{
		$this->filter = $filter;
		$this->currentTimestamp = time();
		$this->connection = Application::getInstance()->getConnection();
	}

	public function prepareQuery(Query $query): void
	{
		$this->initAlias = $query->getInitAlias();

		parent::prepareQuery($query);
	}

	public function prepareFilter(): ConditionTree
	{
		$result = new ConditionTree();

		$includeDeleted = (
			isset($this->filter['INCLUDE_DELETED'])
			&& $this->filter['INCLUDE_DELETED'] === true
		);
		if (!$includeDeleted)
		{
			$result->where('IS_DELETED', '=', 'N');
		}

		if (isset($this->filter['ID']))
		{
			if (is_array($this->filter['ID']))
			{
				$result->whereIn('ID', array_map('intval', $this->filter['ID']));
			}
			else
			{
				$result->where('ID', '=', (int)$this->filter['ID']);
			}
		}

		if (isset($this->filter['!ID']))
		{
			if (is_array($this->filter['!ID']))
			{
				$result->whereNotIn('ID', array_map('intval', $this->filter['!ID']));
			}
			else
			{
				$result->whereNot('ID', (int)$this->filter['!ID']);
			}
		}

		if (isset($this->filter['CREATED_BY']))
		{
			if (is_array($this->filter['CREATED_BY']))
			{
				$result->whereIn('CREATED_BY', array_map('intval', $this->filter['CREATED_BY']));
			}
			else
			{
				$result->where('CREATED_BY', '=', (int)$this->filter['CREATED_BY']);
			}
		}

		// @todo recurring bookings are not supported
		if (isset($this->filter['IS_CONFIRMED']))
		{
			$result->where('IS_CONFIRMED', '=', (bool)$this->filter['IS_CONFIRMED']);
		}

		if (isset($this->filter['VISIT_STATUS']))
		{
			if (is_array($this->filter['VISIT_STATUS']))
			{
				$result->whereIn('VISIT_STATUS', $this->filter['VISIT_STATUS']);
			}
			else
			{
				$result->where('VISIT_STATUS', '=', $this->filter['VISIT_STATUS']);
			}
		}

		if (isset($this->filter['SOURCE']))
		{
			if (is_array($this->filter['SOURCE']))
			{
				$result->whereIn('SOURCE', $this->filter['SOURCE']);
			}
			else
			{
				$result->where('SOURCE', '=', $this->filter['SOURCE']);
			}
		}


		$this->applyIsDelayedFilter($result);
		$this->applyHasResourcesFilter($result);
		$this->applyHasClientsFilter($result);
		$this->applyHasCounterOfTypeFilter($result);

		if (isset($this->filter['HAS_COUNTERS_USER_ID']))
		{
			$result->where($this->getHasCountersUserIdConditionTree((int)$this->filter['HAS_COUNTERS_USER_ID']));
		}

		if (isset($this->filter['RESOURCE_ID']) && is_array($this->filter['RESOURCE_ID']))
		{
			$result->where($this->getResourcesConditionTree((array)$this->filter['RESOURCE_ID']));
		}

		if (
			isset($this->filter['RESOURCE_ID_OR_HAS_COUNTERS_USER_ID']['HAS_COUNTERS_USER_ID'])
			&& isset($this->filter['RESOURCE_ID_OR_HAS_COUNTERS_USER_ID']['RESOURCE_ID'])
			&& is_array($this->filter['RESOURCE_ID_OR_HAS_COUNTERS_USER_ID']['RESOURCE_ID'])
		)
		{
			$result
				->where(
					Query::filter()
						->logic('OR')
						->where($this->getHasCountersUserIdConditionTree(
							(int)$this->filter['RESOURCE_ID_OR_HAS_COUNTERS_USER_ID']['HAS_COUNTERS_USER_ID'])
						)
						->where($this->getResourcesConditionTree(
							$this->filter['RESOURCE_ID_OR_HAS_COUNTERS_USER_ID']['RESOURCE_ID'])
						)
				)
			;
		}

		if (isset($this->filter['MODULE_ID']) && is_string($this->filter['MODULE_ID']))
		{
			$result->where('TYPE.MODULE_ID', '=', $this->filter['MODULE_ID']);
		}

		$crmModuleId = 'crm';
		$crmClientProvider = Container::getProviderManager()::getProviderByModuleId($crmModuleId)?->getClientProvider();
		if ($crmClientProvider)
		{
			$crmClientTypes = $crmClientProvider->getClientTypeCollection();

			foreach ($crmClientTypes as $crmClientType)
			{
				$entityId = $this->getEntityId($crmModuleId, $crmClientType->getCode());
				if (!$entityId)
				{
					continue;
				}

				$result
					->whereIn(
						'CLIENTS.CLIENT_TYPE_ID',
						new SqlExpression(
							ClientTypeTable::query()
								->setSelect(['ID'])
								->where('MODULE_ID', '=', $crmModuleId)
								->where('CODE', '=', $crmClientType->getCode())
								->getQuery()
						)
					)
					->whereIn('CLIENTS.CLIENT_ID', $entityId)
				;
			}
		}

		if (isset($this->filter['CREATED_WITHIN']['FROM']))
		{
			$result->where('CREATED_AT', '>=', $this->filter['CREATED_WITHIN']['FROM']);
		}

		if (isset($this->filter['CREATED_WITHIN']['TO']))
		{
			$result->where('CREATED_AT', '<', $this->filter['CREATED_WITHIN']['TO']);
		}

		if (
			isset($this->filter['WITHIN']['DATE_FROM'])
			&& isset($this->filter['WITHIN']['DATE_TO'])
		)
		{
			$periodDateFrom = (int)$this->filter['WITHIN']['DATE_FROM'];
			$periodDateTo = (int)$this->filter['WITHIN']['DATE_TO'];

			$bookingIds = array_column(
				Application::getConnection()->query("
					SELECT ID
					FROM " . BookingTable::getTableName() . "
					WHERE
						DATE_FROM < $periodDateTo
						AND DATE_MAX >= $periodDateTo
						AND IS_DELETED = 'N'
					UNION
					SELECT ID
					FROM " . BookingTable::getTableName() . "
					WHERE
						DATE_FROM <= $periodDateFrom
						AND DATE_MAX > $periodDateFrom
						AND IS_DELETED = 'N'
					UNION
					SELECT ID
					FROM " . BookingTable::getTableName() . "
					WHERE
						DATE_FROM >= $periodDateFrom
						AND DATE_MAX < $periodDateTo
						AND IS_DELETED = 'N'	
					")->fetchAll(),
				'ID'
			);

			$result->whereIn('ID', empty($bookingIds) ? [0] : $bookingIds);
		}
		else if (isset($this->filter['WITHIN']['DATE_FROM']))
		{
			$result->where('DATE_FROM', '>=', $this->filter['WITHIN']['DATE_FROM']);
		}

		if (
			isset($this->filter['EXTERNAL_DATA_ITEM'])
			&& $this->filter['EXTERNAL_DATA_ITEM'] instanceof ExternalDataItem
		)
		{
			$externalDataItem = $this->filter['EXTERNAL_DATA_ITEM'];

			$result
				->where('EXTERNAL_DATA.MODULE_ID', $externalDataItem->getModuleId())
				->where('EXTERNAL_DATA.ENTITY_TYPE_ID', $externalDataItem->getEntityTypeId())
				->where('EXTERNAL_DATA.VALUE', $externalDataItem->getValue())
			;
		}

		return $result;
	}

	private function applyHasResourcesFilter(ConditionTree $result): void
	{
		if (isset($this->filter['HAS_RESOURCES']))
		{
			$has = (bool)$this->filter['HAS_RESOURCES'];
			$filter = Query::filter()->whereExists(
				new SqlExpression("
					SELECT 1
					FROM " . BookingResourceTable::getTableName() . "
					WHERE
						BOOKING_ID = " . $this->initAlias . ".ID
				")
			);

			if ($has)
			{
				$result->where($filter);
			}
			else
			{
				$result->whereNot($filter);
			}
		}
	}

	private function applyHasClientsFilter(ConditionTree $result): void
	{
		if (isset($this->filter['HAS_CLIENTS']))
		{
			$hasClients = (bool)$this->filter['HAS_CLIENTS'];
			$hasClientsFilter = Query::filter()->whereExists(
				new SqlExpression("
					SELECT 1
					FROM " . BookingClientTable::getTableName() . "
					WHERE
						ENTITY_ID = " . $this->initAlias . ".ID
						AND ENTITY_TYPE = '" . $this->connection->getSqlHelper()->forSql(EntityType::Booking->value) . "'
				")
			);

			if ($hasClients)
			{
				$result->where($hasClientsFilter);
			}
			else
			{
				$result->whereNot($hasClientsFilter);
			}
		}
	}

	private function applyIsDelayedFilter(ConditionTree $result): void
	{
		// @todo recurring bookings are not supported
		if (isset($this->filter['IS_DELAYED']))
		{
			//@todo consider using counters here?
			$isOn = (bool)$this->filter['IS_DELAYED'];
			$filter = Query::filter()
				//@todo 5 min should not be hardcoded here, see DELAYED_COUNTER_DELAY on ResourceNotificationSettingsTable
				->where('DATE_FROM', '<', $this->currentTimestamp - Time::CONSIDER_BOOKING__DELAYED_AFTER_SECONDS)
				->where('DATE_TO', '>', $this->currentTimestamp)
				->whereIn('VISIT_STATUS', [
					BookingVisitStatus::Unknown->value,
					BookingVisitStatus::NotVisited->value,
				])
			;

			if ($isOn)
			{
				$result->where($filter);
			}
			else
			{
				$result->whereNot($filter);
			}
		}
	}

	private function applyHasCounterOfTypeFilter(ConditionTree $result): void
	{
		if (!isset($this->filter['HAS_COUNTER_OF_TYPE']))
		{
			return;
		}

		$counter = (string)$this->filter['HAS_COUNTER_OF_TYPE'];
		if (!CounterDictionary::isExists($counter))
		{
			return;
		}

		$result->whereExists(
			new SqlExpression("
			SELECT 1
			FROM " . ScorerTable::getTableName() . "
			WHERE
				ENTITY_ID = " . $this->initAlias . ".ID
				AND TYPE = '" . $this->connection->getSqlHelper()->forSql($counter) . "'
				AND VALUE > 0
				AND USER_ID = " . (int)CurrentUser::get()->getId()
			));
	}

	private function getHasCountersUserIdConditionTree(int $userId): ConditionTree
	{
		return Query::filter()->whereExists(
			new SqlExpression("
					SELECT 1
					FROM " . ScorerTable::getTableName() . "
					WHERE
						ENTITY_ID = " . $this->initAlias . ".ID
						AND USER_ID = " . (int)$userId . "
						AND VALUE > 0
				")
		);
	}

	private function getResourcesConditionTree(array $resourceIds): ConditionTree
	{
		return Query::filter()->whereIn('RESOURCES.RESOURCE.ID', $resourceIds);
	}

	private function getEntityId(string $moduleId, string $code): array
	{
		if (
			isset($this->filter['CLIENT']['ENTITIES'])
			&& is_array($this->filter['CLIENT']['ENTITIES'])
		)
		{
			$entities = $this->filter['CLIENT']['ENTITIES'];
			foreach ($entities as $entity)
			{
				if (
					is_array($entity)
					&& isset(
						$entity['MODULE'],
						$entity['CODE'],
						$entity['ID'],
					)
					&& $entity['MODULE'] === $moduleId
					&& $entity['CODE'] === $code
				)
				{
					$entityId = $entity['ID'];

					return (is_array($entityId)) ? $entityId : [$entityId];
				}
			}
		}

		$filterKey = mb_strtoupper($moduleId) . '_' . $code . '_ID';
		if (isset($this->filter[$filterKey]))
		{
			return $this->filter[$filterKey];
		}

		return [];
	}
}

<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider\Params\Booking;

use Bitrix\Booking\Entity\Booking\BookingVisitStatus;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Model\BookingClientTable;
use Bitrix\Booking\Internals\Model\BookingMessageFailureLogTable;
use Bitrix\Booking\Internals\Model\BookingMessageTable;
use Bitrix\Booking\Internals\Model\BookingResourceTable;
use Bitrix\Booking\Internals\Model\ClientTypeTable;
use Bitrix\Booking\Internals\Model\ScorerTable;
use Bitrix\Booking\Internals\Service\Time;
use Bitrix\Booking\Provider\Params\Filter;
use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\DB\PgsqlConnection;

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

		if (
			isset($this->filter['STARTS_IN_LESS_THAN'])
			|| isset($this->filter['IS_SAME_DAY_OR_EARLY_MORNING_START'])
		)
		{
			$query->registerRuntimeField(
				'',
				new ExpressionField(
					'STARTS_IN',
					"
						CASE WHEN (
							%s > " . $this->currentTimestamp . "
						)
						THEN %s - " . $this->currentTimestamp . "
						ELSE 0
						END
					",
					[
						'DATE_FROM',
						'DATE_FROM',
					]
				)
			);
		}

		if (isset($this->filter['WITHIN_CURRENT_DAYTIME']))
		{
			$query->registerRuntimeField(
				'',
				new ExpressionField(
					'CURRENT_HOUR',
					"EXTRACT(HOUR FROM " . $this->connection->getSqlHelper()->addSecondsToDateTime('%s', $this->fromUnixTimeSqlFn($this->currentTimestamp)) . ")",
					['TIMEZONE_FROM_OFFSET']
				)
			);
		}

		if (isset($this->filter['IS_SAME_DAY_OR_EARLY_MORNING_START']))
		{
			$query->registerRuntimeField(
				'',
				new ExpressionField(
					'IS_SAME_DAY',
					"
						CASE WHEN (
							EXTRACT(DAY FROM " . $this->connection->getSqlHelper()->addSecondsToDateTime('%s', $this->fromUnixTimeSqlFn('%s')) . ")
							=
							EXTRACT(DAY FROM " . $this->connection->getSqlHelper()->addSecondsToDateTime('%s', $this->fromUnixTimeSqlFn($this->currentTimestamp)) . ")
						)
						THEN 1
						ELSE 0
						END
					",
					[
						'DATE_FROM',
						'TIMEZONE_FROM_OFFSET',
						'TIMEZONE_FROM_OFFSET',
					]
				)
			);
		}

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

		$this->applyIsDelayedFilter($result);
		$this->applyStartsInLessThanFilter($result);
		$this->applyIsSameDayOrEarlyMorningStartFilter($result);
		$this->applyWithinCurrentDayTimeFilter($result);
		$this->applyMessageSentFilter($result);
		$this->applyMessageTriedFilter($result);
		$this->applyHasResourcesFilter($result);
		$this->applyHasClientsFilter($result);
		$this->applyNotificationsSettingsFilter($result);

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
				$filterKey = mb_strtoupper($crmModuleId) . '_' . $crmClientType->getCode() . '_ID';
				if (isset($this->filter[$filterKey]))
				{
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
						->whereIn('CLIENTS.CLIENT_ID', $this->filter[$filterKey])
					;
				}
			}
		}

		if (
			isset($this->filter['CREATED_WITHIN']['FROM'])
			&& isset($this->filter['CREATED_WITHIN']['TO'])
		)
		{
			$result
				->where('CREATED_AT', '>=', $this->filter['CREATED_WITHIN']['FROM'])
				->where('CREATED_AT', '<', $this->filter['CREATED_WITHIN']['TO']);
		}

		if (
			isset($this->filter['WITHIN']['DATE_FROM'])
			&& isset($this->filter['WITHIN']['DATE_TO'])
		)
		{
			$result
				->where(
					Query::filter()
						->logic('OR')
						->where(
							Query::filter()
								->logic('OR')
								->where(
									Query::filter()
										->where('DATE_FROM', '<=', $this->filter['WITHIN']['DATE_FROM'])
										->where('DATE_MAX', '>', $this->filter['WITHIN']['DATE_FROM'])
								)
								->where(
									Query::filter()
										->where('DATE_FROM', '<', $this->filter['WITHIN']['DATE_TO'])
										->where('DATE_MAX', '>=', $this->filter['WITHIN']['DATE_TO'])
								)
						)
						->where(
							Query::filter()
								->logic('OR')
								->where(
									Query::filter()
										->where('DATE_FROM', '>=', $this->filter['WITHIN']['DATE_FROM'])
										->where('DATE_FROM', '<', $this->filter['WITHIN']['DATE_TO'])
								)
								->where(
									Query::filter()
										->where('DATE_MAX', '>', $this->filter['WITHIN']['DATE_FROM'])
										->where('DATE_MAX', '<=', $this->filter['WITHIN']['DATE_TO'])
								)
						)
				)
			;
		}

		return $result;
	}

	private function applyMessageSentFilter(ConditionTree $result): void
	{
		if (
			!isset($this->filter['MESSAGE_OF_TYPE_SENT'])
			|| !is_array($this->filter['MESSAGE_OF_TYPE_SENT'])
		)
		{
			return;
		}

		foreach ($this->filter['MESSAGE_OF_TYPE_SENT'] as $filterItem)
		{
			if (
				!isset($filterItem['EXISTS'])
				|| !isset($filterItem['TYPE'])
			)
			{
				continue;
			}

			$exists = (bool)$filterItem['EXISTS'];

			$minutesFilter =
				isset($filterItem['MINUTES'])
					? "
						AND CREATED_AT > " . $this->connection->getSqlHelper()->addSecondsToDateTime('-' . (int)$filterItem['MINUTES'] * Time::SECONDS_IN_MINUTE) . "
						AND CREATED_AT <= NOW()
					"
					: ""
			;

			$countFilter =
				isset($filterItem['COUNT'])
					? "
						GROUP BY BOOKING_ID, NOTIFICATION_TYPE
						HAVING COUNT(1) >= " . (int)$filterItem['COUNT'] . "
					"
					: ""
			;

			$sqlExpression = new SqlExpression("
				SELECT 1
				FROM " . BookingMessageTable::getTableName() . "
				WHERE
					BOOKING_ID = " . $this->initAlias . ".ID
					AND NOTIFICATION_TYPE = '" . $this->connection->getSqlHelper()->forSql((string)$filterItem['TYPE']) . "'
					" . $minutesFilter . "
				" . $countFilter . "
			");

			$queryFilter = Query::filter()->whereExists($sqlExpression);
			if ($exists)
			{
				$result->where($queryFilter);
			}
			else
			{
				$result->whereNot($queryFilter);
			}
		}
	}

	private function applyMessageTriedFilter(ConditionTree $result): void
	{
		if (
			!isset($this->filter['MESSAGE_OF_TYPE_TRIED'])
			|| !is_array($this->filter['MESSAGE_OF_TYPE_TRIED'])
		)
		{
			return;
		}

		foreach ($this->filter['MESSAGE_OF_TYPE_TRIED'] as $filterItem)
		{
			if (
				!isset($filterItem['EXISTS'])
				|| !isset($filterItem['TYPE'])
			)
			{
				continue;
			}

			$exists = (bool)$filterItem['EXISTS'];

			$minutesFilter =
				isset($filterItem['MINUTES'])
					? "
						AND CREATED_AT > " . $this->connection->getSqlHelper()->addSecondsToDateTime('-' . (int)$filterItem['MINUTES'] * Time::SECONDS_IN_MINUTE) . "
						AND CREATED_AT <= NOW()
					"
					: ""
			;

			$countFilter =
				isset($filterItem['COUNT'])
					? "
						GROUP BY BOOKING_ID, NOTIFICATION_TYPE
						HAVING COUNT(1) >= " . (int)$filterItem['COUNT'] . "
					"
					: ""
			;

			$sqlExpression = new SqlExpression("
				SELECT 1
				FROM " . BookingMessageFailureLogTable::getTableName() . "
				WHERE
					BOOKING_ID = " . $this->initAlias . ".ID
					AND NOTIFICATION_TYPE = '" . $this->connection->getSqlHelper()->forSql((string)$filterItem['TYPE']) . "'
					" . $minutesFilter . "
				" . $countFilter . "
			");

			$queryFilter = Query::filter()->whereExists($sqlExpression);
			if ($exists)
			{
				$result->where($queryFilter);
			}
			else
			{
				$result->whereNot($queryFilter);
			}
		}
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
						BOOKING_ID = " . $this->initAlias . ".ID
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

	private function applyNotificationsSettingsFilter(ConditionTree $result): void
	{
		$fields = [
			'IS_PRIMARY_RESOURCE_INFO_ON',
			'IS_PRIMARY_RESOURCE_CONFIRMATION_ON',
			'IS_PRIMARY_RESOURCE_REMINDER_ON',
			'IS_PRIMARY_RESOURCE_FEEDBACK_ON',
			'IS_PRIMARY_RESOURCE_DELAYED_ON',
		];

		foreach ($fields as $field)
		{
			if (isset($this->filter[$field]))
			{
				$result->where($field, $this->filter[$field] ? 'Y' : 'N');
			}
		}
	}

	private function applyIsDelayedFilter(ConditionTree $result): void
	{
		// @todo recurring bookings are not supported
		if (isset($this->filter['IS_DELAYED']))
		{
			$isOn = (bool)$this->filter['IS_DELAYED'];
			$filter = Query::filter()
				->where('DATE_FROM', '<', $this->currentTimestamp - Time::SECONDS_IN_MINUTE * 5)
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

	private function applyStartsInLessThanFilter(ConditionTree $result): void
	{
		// @todo recurring bookings are not supported
		if (isset($this->filter['STARTS_IN_LESS_THAN']))
		{
			$result
				->where('STARTS_IN', '<=', new SqlExpression('?i', (int)$this->filter['STARTS_IN_LESS_THAN']))
				->where('STARTS_IN', '>', new SqlExpression('?i', 0))
			;
		}
	}

	private function applyIsSameDayOrEarlyMorningStartFilter(ConditionTree $result): void
	{
		// @todo recurring bookings are not supported
		if (isset($this->filter['IS_SAME_DAY_OR_EARLY_MORNING_START']))
		{
			$isOn = (bool)$this->filter['IS_SAME_DAY_OR_EARLY_MORNING_START'];

			$silencePeriodInHours =  Time::HOURS_IN_DAY - (Time::DAYTIME_END_HOUR - Time::DAYTIME_START_HOUR);
			$notificationGapInHour = 1;

			$filter = Query::filter()
				->logic('OR')
				->where(
					'IS_SAME_DAY',
					'=',
					new SqlExpression('?i', 1)
				)
				->where(
					Query::filter()
						->where(
							'STARTS_IN',
							'<=',
							new SqlExpression(
								'?i',
								($silencePeriodInHours + $notificationGapInHour) * Time::SECONDS_IN_HOUR
							)
						)
						->where('STARTS_IN', '>', new SqlExpression('?i', 0))
				)
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

	private function applyWithinCurrentDayTimeFilter(ConditionTree $result): void
	{
		// @todo recurring bookings are not supported
		if (isset($this->filter['WITHIN_CURRENT_DAYTIME']))
		{
			$isOn = (bool)$this->filter['WITHIN_CURRENT_DAYTIME'];
			$filter = Query::filter()
				->where('CURRENT_HOUR', '>=', new SqlExpression('?i', Time::DAYTIME_START_HOUR))
				->where('CURRENT_HOUR', '<', new SqlExpression('?i', Time::DAYTIME_END_HOUR))
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

	private function fromUnixTimeSqlFn($timestamp): string
	{
		if ($this->connection instanceof PgsqlConnection)
		{
			return 'to_timestamp(' . $timestamp . ')';
		}

		return 'FROM_UNIXTIME(' . $timestamp . ')';
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

<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider\Params\Booking;

use Bitrix\Booking\Internals\Model\ScorerTable;
use Bitrix\Booking\Internals\Service\Time;
use Bitrix\Booking\Provider\Params\Filter;
use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\DB\PgsqlConnection;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

class SpecialBookingFilter extends Filter
{
	private const FILTER_OLD_COUNTERS = 'FILTER_OLD_COUNTERS';

	private readonly Connection $connection;

	public function __construct(
		private readonly string $filter,
		private readonly array $params,
	)
	{
		$this->connection = Application::getInstance()->getConnection();
	}

	public function prepareFilter(): ConditionTree
	{
		return match ($this->filter)
		{
			self::FILTER_OLD_COUNTERS => $this->applyFilterOldCounters(),
		};
	}

	/**
	 * @param string[] $types
	 */
	public static function buildOldCountersFilter(array $types): static
	{
		return new static(static::FILTER_OLD_COUNTERS, ['types' => $types]);
	}

	/**
	 * Booking should fit to conditions:
	 * - already ended
	 * - started less than 24 hours ago
	 * - has at least one counter of params['types']
	 * - started not today
	 */
	private function applyFilterOldCounters(): ConditionTree
	{
		$result = new ConditionTree();

		$currentTimestamp = time();
		$dayAgoTimestamp = $currentTimestamp - Time::SECONDS_IN_DAY;
		$sqlHelper = $this->connection->getSqlHelper();

		$scorerTableName = ScorerTable::getTableName();

		$escapedTypes = array_map(static fn (string $type) => $sqlHelper->forSql($type), $this->params['types']);

		$expr = "
			%1\$s < $currentTimestamp
			AND %2\$s > $dayAgoTimestamp
			AND EXISTS (
				SELECT 1
				FROM $scorerTableName s
				WHERE s.ENTITY_ID = %3\$s
				AND s.TYPE IN ('" . implode("', '", $escapedTypes) . "')
			)
			AND (
				EXTRACT(DAY FROM " . $sqlHelper->addSecondsToDateTime('%4$s', $this->fromUnixTimeSqlFn('%2$s')) . ")
				!=
				EXTRACT(DAY FROM " . $sqlHelper->addSecondsToDateTime('%4$s', $this->fromUnixTimeSqlFn($currentTimestamp)) . ")
			)
		";

		// keys presented only for more readable code,
		// actual params position|order in array should be exact as in query
		$args = [
			1 => 'DATE_TO',
			2 => 'DATE_FROM',
			3 => 'ID',
			4 => 'TIMEZONE_FROM_OFFSET',
		];

		$result->whereExpr($expr, $args);

		return $result;
	}

	private function fromUnixTimeSqlFn($timestamp): string
	{
		if ($this->connection instanceof PgsqlConnection)
		{
			return 'to_timestamp(' . $timestamp . ')';
		}

		return 'FROM_UNIXTIME(' . $timestamp . ')';
	}
}

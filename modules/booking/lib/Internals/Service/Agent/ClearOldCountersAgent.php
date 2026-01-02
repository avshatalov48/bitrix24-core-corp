<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Agent;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Service\CounterDictionary;
use Bitrix\Booking\Provider\Params\Booking\SpecialBookingFilter;

class ClearOldCountersAgent
{
	private const SUPPORTED_TYPES = [CounterDictionary::BookingUnConfirmed, CounterDictionary::BookingDelayed];

	public static function execute(): string
	{
		$bookingIds = self::getBookingIdsToDelete();

		if (empty($bookingIds))
		{
			return self::getExecutionString();
		}

		self::clearCountersByBookingIds($bookingIds);

		return self::getExecutionString();
	}

	private static function getExecutionString(): string
	{
		return '\\' . static::class . '::execute();';
	}

	/**
	 * @return int[]
	 */
	private static function getBookingIdsToDelete(): array
	{
		$query = Container::getBookingRepository()
			->getQuery(SpecialBookingFilter::buildOldCountersFilter(self::getSupportedTypes()))
		;

		$bookingIdRows = $query
			->setSelect(['ID'])
			->fetchAll()
		;

		return array_map('intval', array_column($bookingIdRows, 'ID'));
	}

	private static function clearCountersByBookingIds(array $bookingIds): void
	{
		$counterRepository = Container::getCounterRepository();

		$affectedUsers = $counterRepository->getUsersByCounterType(
			entityIds: $bookingIds,
			types: self::SUPPORTED_TYPES,
		);
		if (empty($affectedUsers))
		{
			return;
		}

		$counterRepository->downMultiple($bookingIds, self::SUPPORTED_TYPES);

		foreach ($affectedUsers as $row)
		{
			$userId = (int)$row['USER_ID'];

			\CUserCounter::Set(
				$userId,
				CounterDictionary::LeftMenu->value,
				$counterRepository->get($userId, CounterDictionary::Total),
				'**',
			);
		}
	}

	/**
	 * @return string[]
	 */
	private static function getSupportedTypes(): array
	{
		return array_map(
			static fn (CounterDictionary $type) => $type->value,
			self::SUPPORTED_TYPES,
		);
	}
}

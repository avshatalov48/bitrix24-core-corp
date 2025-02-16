<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider;

use Bitrix\Booking\Integration\Booking\ProviderInterface;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Entity;
use DateTimeImmutable;

class MoneyStatisticsProvider
{
	private BookingProvider $bookingProvider;
	private ProviderInterface|null $provider;

	public function __construct()
	{
		$this->bookingProvider = new BookingProvider();
		$this->provider = Container::getProviderManager()::getCurrentProvider();
	}

	public function get(int $userId): array
	{
		$firstDateOfThisMonth = (new DateTimeImmutable('first day of this month'))->setTime(0, 0, 0);
		$lastDateOfThisMonth = (new DateTimeImmutable('last day of this month'))->setTime(23, 59, 59);

		$todayStart = (new DateTimeImmutable('today'))->setTime(0, 0, 0);
		$todayEnd = (new DateTimeImmutable('today'))->setTime(23, 59, 59);

		return [
			'today' => $this->provider->getDataProvider()->getMoneyStatistics(
				$this->bookingProvider->getList(
					userId: $userId,
					filter: [
						'WITHIN' => [
							'DATE_FROM' => $todayStart->getTimestamp() - \CTimeZone::GetOffset(),
							'DATE_TO' => $todayEnd->getTimestamp() - \CTimeZone::GetOffset(),
						],
						'VISIT_STATUS' => [
							Entity\Booking\BookingVisitStatus::Visited->value,
							Entity\Booking\BookingVisitStatus::Unknown->value,
						],
					],
					select: ['EXTERNAL_DATA'],
					withExternalData: true,
				)->getExternalDataCollection(),
			),
			'month' => $this->provider->getDataProvider()->getMoneyStatistics(
				$this->bookingProvider->getList(
					userId: $userId,
					filter: [
						'WITHIN' => [
							'DATE_FROM' => $firstDateOfThisMonth->getTimestamp() - \CTimeZone::GetOffset(),
							'DATE_TO' => $lastDateOfThisMonth->getTimestamp() - \CTimeZone::GetOffset(),
						],
						'VISIT_STATUS' => [
							Entity\Booking\BookingVisitStatus::Visited->value,
							Entity\Booking\BookingVisitStatus::Unknown->value,
						],
					],
					select: ['EXTERNAL_DATA'],
					withExternalData: true,
				)->getExternalDataCollection(),
			),
		];
	}
}

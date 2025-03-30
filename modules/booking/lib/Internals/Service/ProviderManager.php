<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Interfaces\ClientProviderInterface;
use Bitrix\Booking\Interfaces\ProviderInterface;
use Bitrix\Main\Event;

class ProviderManager
{
	/** @var ClientProviderInterface[] */
	private static array|null $providers = null;

	private const ON_GET_PROVIDER_EVENT = 'onGetProvider';

	public static function getCurrentProvider(): ProviderInterface|null
	{
		self::initProviders();

		return empty(self::$providers) ? null : self::$providers[0];
	}

	public static function getProviderByBooking(Booking $booking): ProviderInterface|null
	{
		self::initProviders();

		$clientCollection = $booking->getClientCollection();
		foreach ($clientCollection as $client)
		{
			$clientType = $client->getType();
			if (!$clientType)
			{
				continue;
			}

			return self::getProviderByModuleId($clientType->getModuleId());
		}

		//@todo try by data

		return null;
	}

	public static function getProviderByModuleId(string $moduleId): ProviderInterface|null
	{
		self::initProviders();

		foreach (self::$providers as $provider)
		{
			if ($provider->getModuleId() === $moduleId)
			{
				return $provider;
			}
		}

		return null;
	}

	private static function initProviders(): void
	{
		if (!is_null(self::$providers))
		{
			return;
		}

		self::$providers = [];

		$event = new Event('booking', self::ON_GET_PROVIDER_EVENT);
		$event->send();

		$resultList = $event->getResults();
		if (is_array($resultList))
		{
			foreach ($resultList as $eventResult)
			{
				$provider = $eventResult->getParameters();
				if ($provider instanceof ProviderInterface)
				{
					self::$providers[] = $provider;
				}
			}
		}
	}
}

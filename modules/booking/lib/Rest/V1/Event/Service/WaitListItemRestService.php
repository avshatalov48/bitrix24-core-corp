<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Event\Service;

use Bitrix\Booking\Entity\WaitListItem\WaitListItem;
use Bitrix\Booking\Internals\Trait\SingletonTrait;
use Bitrix\Main\Event;

class WaitListItemRestService implements RestServiceInterface
{
	use SingletonTrait;

	protected const MODULE_ID = 'booking';

	public function getEvents(): array
	{
		return [
			'onBookingWaitListItemAdd' => [
				self::MODULE_ID,
				'onWaitListItemAdd',
				[
					self::class,
					'getRestParams',
				],
			],
			'onBookingWaitListItemUpdate' => [
				self::MODULE_ID,
				'onWaitListItemUpdate',
				[
					self::class,
					'getRestParams',
				],
			],
			'onBookingWaitListItemDelete' => [
				self::MODULE_ID,
				'onWaitListItemDelete',
				[
					self::class,
					'getRestParams',
				],
			],
		];
	}

	/**
	 * @var Event[] $eventList
	 */
	public static function getRestParams(array $eventList): array
	{
		$event = $eventList[0] ?? null;

		if (!$event)
		{
			return [];
		}

		/** @var WaitListItem $waitListItem */
		$waitListItem = $event->getParameter('waitListItem');
		$waitListItemId = (int)$waitListItem->getId();

		return [
			'ID' => $waitListItemId,
		];
	}
}

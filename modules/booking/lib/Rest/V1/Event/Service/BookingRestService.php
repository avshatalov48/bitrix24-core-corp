<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Event\Service;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Internals\Trait\SingletonTrait;
use Bitrix\Main\Event;

class BookingRestService implements RestServiceInterface
{
	use SingletonTrait;

	protected const MODULE_ID = 'booking';

    public function getEvents(): array
    {
        return [
            'onBookingAdd' => [
               self::MODULE_ID,
                'onBookingAdd',
                [
                    self::class,
                    'getRestParams',
                ],
            ],
            'onBookingUpdate' => [
                self::MODULE_ID,
                'onBookingUpdate',
                [
                    self::class,
                    'getRestParams',
                ],
            ],
            'onBookingDelete' => [
                self::MODULE_ID,
                'onBookingDelete',
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

		if ($event->getEventType() === 'onBookingDelete')
		{
			$bookingId = (int)$event->getParameter('bookingId');
		}
		else
		{
			/** @var Booking $booking */
			$booking = $event->getParameter('booking');
			$bookingId = (int)$booking->getId();
		}

        return [
            'ID' => $bookingId,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Event\Service;

use Bitrix\Booking\Entity\Resource\Resource;
use Bitrix\Booking\Internals\Trait\SingletonTrait;
use Bitrix\Main\Event;

class ResourceRestService implements RestServiceInterface
{
	use SingletonTrait;

	protected const MODULE_ID = 'booking';

    public function getEvents(): array
    {
        return [
            'onBookingResourceAdd' => [
                self::MODULE_ID,
                'onResourceAdd',
                [
                    self::class,
                    'getRestParams',
                ],
            ],
            'onBookingResourceUpdate' => [
                self::MODULE_ID,
                'onResourceUpdate',
                [
                    self::class,
                    'getRestParams',
                ],
            ],
            'onBookingResourceDelete' => [
                self::MODULE_ID,
                'onResourceDelete',
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

		if ($event->getEventType() === 'onResourceDelete')
		{
			$resourceId = (int)$event->getParameter('resourceId');
		}
		else
		{
			/** @var Resource $resource */
			$resource = $event->getParameter('resource');
			$resourceId = (int)$resource->getId();
		}

		return [
			'ID' => $resourceId,
		];
	}
}

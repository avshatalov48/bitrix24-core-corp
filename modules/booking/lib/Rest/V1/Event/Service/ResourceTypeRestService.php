<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Event\Service;

use Bitrix\Booking\Entity\ResourceType\ResourceType;
use Bitrix\Booking\Internals\Trait\SingletonTrait;
use Bitrix\Main\Event;

class ResourceTypeRestService implements RestServiceInterface
{
	use SingletonTrait;

	protected const MODULE_ID = 'booking';

    public function getEvents(): array
    {
        return [
            'onBookingResourceTypeAdd' => [
                self::MODULE_ID,
                'onResourceTypeAdd',
                [
                    self::class,
                    'getRestParams',
                ],
            ],
            'onBookingResourceTypeUpdate' => [
                self::MODULE_ID,
                'onResourceTypeUpdate',
                [
                    self::class,
                    'getRestParams',
                ],
            ],
            'onBookingResourceTypeDelete' => [
                self::MODULE_ID,
                'onResourceTypeDelete',
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

		if ($event->getEventType() === 'onResourceTypeDelete')
		{
			$resourceTypeId = (int)$event->getParameter('resourceTypeId');
		}
		else
		{
			/** @var ResourceType $resourceType */
			$resourceType = $event->getParameter('resourceType');
			$resourceTypeId = (int)$resourceType->getId();
		}

		return [
			'ID' => $resourceTypeId,
		];
	}
}

<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Entity\Booking\BookingVisitStatus;
use Bitrix\Booking\Internals\CounterDictionary;
use Bitrix\Booking\Internals\Journal\EventProcessor\PushPull\PushPullCommandType;
use Bitrix\Booking\Internals\NotificationType;
use Bitrix\Booking\Internals\NotificationTemplateType;

class Dictionary extends BaseController
{
	public function getAction(): array
	{
		return [
			'counters' => CounterDictionary::toArray(),
			'pushCommands' => PushPullCommandType::toArray(),
			'notifications' => NotificationType::toArray(),
			'notificationTemplateTypes' => NotificationTemplateType::toArray(),
			'bookings' => [
				'visitStatuses' => BookingVisitStatus::toArray(),
			],
		];
	}
}

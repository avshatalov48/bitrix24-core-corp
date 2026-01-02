<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Logger;

class EventLogger
{
	public function log(
		LogLevelEnum $level,
		string $message,
		EventTypeEnum $eventType = EventTypeEnum::Common
	): void
	{
		\CEventLog::Add([
			'SEVERITY' => $level->value,
			'AUDIT_TYPE_ID' => 'BOOKING_MODULE_EVENT_' . $eventType->value,
			'MODULE_ID' => 'booking',
			'ITEM_ID' => '',
			'DESCRIPTION' => $message,
		]);
	}
}

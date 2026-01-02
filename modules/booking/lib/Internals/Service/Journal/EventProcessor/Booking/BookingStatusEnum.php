<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Journal\EventProcessor\Booking;

enum BookingStatusEnum: string
{
	case ConfirmedByClient = 'confirmedByClient';
	case ConfirmedByManager = 'confirmedByManager';
	case ComingSoon = 'comingSoon';
	case DelayedCounterActivated = 'delayedCounterActivated';
	case CanceledByClient = 'canceledByClient';
	case ConfirmCounterActivated = 'confirmCounterActivated';
}

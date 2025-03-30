<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Journal;

enum JournalType: string
{
	case BookingAdded = 'bookingAdded';
	case BookingUpdated = 'bookingUpdated';
	case BookingClientsUpdated = 'bookingClientsUpdated';
	case BookingDeleted = 'bookingDeleted';
	case BookingCanceled = 'bookingCanceled';
	case BookingConfirmed = 'bookingConfirmed';
	case BookingDelayedNotificationInitialized = 'bookingDelayedNotificationInitialized';
	case BookingManagerConfirmNotificationSent = 'bookingManagerConfirmNotificationSent';

	case ResourceAdded = 'resourceAdded';
	case ResourceUpdated = 'resourceUpdated';
	case ResourceDeleted = 'resourceDeleted';

	case ResourceTypeAdded = 'resourceTypeAdded';
	case ResourceTypeUpdated = 'resourceTypeUpdated';
	case ResourceTypeDeleted = 'resourceTypeDeleted';
}

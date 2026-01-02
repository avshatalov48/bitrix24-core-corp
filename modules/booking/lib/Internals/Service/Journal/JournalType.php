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
	case BookingDelayedCounterActivated = 'bookingDelayedCounterActivated';
	case BookingConfirmCounterActivated = 'bookingConfirmCounterActivated';
	case BookingComingSoonNotificationSent = 'bookingComingSoonNotificationSent';

	case ResourceAdded = 'resourceAdded';
	case ResourceUpdated = 'resourceUpdated';
	case ResourceDeleted = 'resourceDeleted';

	case ResourceTypeAdded = 'resourceTypeAdded';
	case ResourceTypeUpdated = 'resourceTypeUpdated';
	case ResourceTypeDeleted = 'resourceTypeDeleted';

	case WaitListItemAdded = 'waitListItemAdded';
	case WaitListItemUpdated = 'waitListItemUpdated';
	case WaitListItemDeleted = 'waitListItemDeleted';
	case WaitListItemClientUpdated = 'waitListItemClientUpdated';
}

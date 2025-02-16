<?php

declare(strict_types=1);

namespace Bitrix\Booking\Access;

enum BookingAction: string
{
	case Create = 'booking_create';
	case Read = 'booking_read';
	case Update = 'booking_update';
	case Delete = 'booking_delete';
}

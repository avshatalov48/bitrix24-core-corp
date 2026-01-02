<?php

declare(strict_types=1);

namespace Bitrix\Crm\Dto\Booking;

enum TagType: string
{
	case Success = 'success';
	case Failure = 'failure';
	case Primary = 'primary';
	case Secondary = 'secondary';
	case Warning = 'warning';
}

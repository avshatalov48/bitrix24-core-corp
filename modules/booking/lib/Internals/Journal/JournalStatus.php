<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Journal;

enum JournalStatus: string
{
	case Pending = 'pending';
	case Processed = 'processed';
	case Error = 'error';
}

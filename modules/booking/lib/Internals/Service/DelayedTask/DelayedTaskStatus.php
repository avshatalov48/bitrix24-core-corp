<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\DelayedTask;

enum DelayedTaskStatus: string
{
	case Pending = 'pending';
	case Processing = 'processing';
	case Processed = 'processed';
	case Error = 'error';
}

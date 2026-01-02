<?php

declare(strict_types=1);

namespace Bitrix\Crm\Dto\Booking\Message;

enum MessageTypeEnum: string
{
	case Info = 'info';
	case Confirmation = 'confirmation';
	case Reminder = 'reminder';
	case Delayed = 'delayed';
	case Feedback = 'feedback';
}

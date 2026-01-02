<?php

declare(strict_types=1);

namespace Bitrix\Crm\Dto\Booking\Message;

enum MessageStatusEnum: string
{
	case Sent = 'sent';
	case Read = 'read';
	case Error = 'error';
}

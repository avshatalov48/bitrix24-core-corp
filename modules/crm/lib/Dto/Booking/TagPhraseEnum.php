<?php

declare(strict_types=1);

namespace Bitrix\Crm\Dto\Booking;

enum TagPhraseEnum: string
{
	case Sent = 'sent';
	case SentNotRead = 'sentNotRead';
	case OpenedNotConfirmed = 'openedNotConfirmed';
	case ConfirmedByClient = 'confirmedByClient';
	case ConfirmedByManager = 'confirmedByManager';
	case ComingSoon = 'comingSoon';
	case CanceledByClient = 'canceledByClient';
}

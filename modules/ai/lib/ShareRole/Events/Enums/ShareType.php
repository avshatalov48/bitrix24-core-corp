<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Events\Enums;

enum ShareType: string
{
	case NotShared = 'shared_no';
	case Shared = 'shared_yes';
}

<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Events\Enums;

enum ShareType: string
{
	case SHARED_NO = 'shared_no';
	case SHARED_YES = 'shared_yes';
}

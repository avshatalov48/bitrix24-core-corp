<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Events\Enums;

enum Status: string
{
	case SUCCESS = 'success';
	case ERROR = 'error';
}

<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Events\Enums;

enum Status: string
{
	case Success = 'success';
	case Error = 'error';
}

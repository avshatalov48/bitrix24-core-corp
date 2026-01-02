<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Logger;

enum LogLevelEnum: string
{
	case Error = 'ERROR';
	case Warning = 'WARNING';
	case Info = 'INFO';
	case Debug = 'DEBUG';
}

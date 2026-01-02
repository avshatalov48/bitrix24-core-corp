<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal;

use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Internals\Log\Log;
use Bitrix\Tasks\Internals\Log\LogFacade;
use Throwable;

class Logger
{
	public function logValidationErrorWarning(ErrorCollection $errors): void
	{
		LogFacade::logValidationErrors($errors);
	}

	public function logError(string|Throwable|Error $error, string $wrapperClass = SystemException::class): void
	{
		LogFacade::handle($error, $wrapperClass);
	}

	public function logWarning(mixed $data, string $marker = Log::DEFAULT_MARKER): void
	{
		LogFacade::log($data, $marker);
	}
}

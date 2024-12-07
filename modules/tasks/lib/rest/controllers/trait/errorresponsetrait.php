<?php

namespace Bitrix\Tasks\Rest\Controllers\Trait;

use Bitrix\Main\Error;
use Bitrix\Tasks\Internals\Log\Logger;
use Throwable;

trait ErrorResponseTrait
{
	protected function buildErrorResponse(string $message = ''): mixed
	{
		$message = empty($message) ? 'Unknown error' : $message;
		$this->errorCollection->setError(new Error($message));
		return null;
	}

	protected function log(Throwable $t, string $marker = 'FLOW_AJAX_ERROR'): void
	{
		Logger::log($t, $marker);
	}
}
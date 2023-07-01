<?php

namespace Bitrix\Tasks\Internals\Task\Placeholder\Exception;

class PlaceholderValidationException extends PlaceholderException
{
	public function __construct(string $className, string $reason = '')
	{
		parent::__construct("Validation failed in {$className}. Reason: {$reason}.");
	}
}
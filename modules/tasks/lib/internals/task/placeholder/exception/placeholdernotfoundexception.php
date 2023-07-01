<?php

namespace Bitrix\Tasks\Internals\Task\Placeholder\Exception;

class PlaceholderNotFoundException extends PlaceholderException
{
	public function __construct(string $className)
	{
		parent::__construct("Placeholder class {$className} not found.");
	}
}
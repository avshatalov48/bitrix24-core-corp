<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Exception;

use Bitrix\Main\Error;

class CommandValidateException extends \Exception
{
	/**
	 * @param Error[] $errors
	 */
	public function __construct(
		public array $errors,
	)
	{
		$message = '';

		foreach ($this->errors as $error)
		{
			$message .= $error->getMessage() . PHP_EOL;
		}

		$this->message = $message;

		parent::__construct();
	}
}
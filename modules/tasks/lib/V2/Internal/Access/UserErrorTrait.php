<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access;

use Bitrix\Main\Error;

trait UserErrorTrait
{
	private array $errors = [];

	public function addUserError(Error $error): void
	{
		$this->errors[] = $error;
	}

	public function getUserErrors(): array
	{
		return $this->errors;
	}

	public function clearUserErrors(): void
	{
		$this->errors = [];
	}
}

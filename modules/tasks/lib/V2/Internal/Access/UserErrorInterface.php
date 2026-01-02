<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access;

use Bitrix\Main\Error;

interface UserErrorInterface
{
	public function addUserError(Error $error): void;

	public function getUserErrors(): array;
}
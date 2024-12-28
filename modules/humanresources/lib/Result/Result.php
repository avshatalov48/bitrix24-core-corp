<?php

namespace Bitrix\HumanResources\Result;

use Bitrix\Main\Error;
use Bitrix\Main;

class Result extends Main\Result
{
	public static function createWithError(string $error): Result
	{
		return (new static())->addError(new Error($error));
	}
}
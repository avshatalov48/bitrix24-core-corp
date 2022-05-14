<?php

namespace Bitrix\Crm\Integrity\CriterionRegistrar;

use Bitrix\Crm\Integrity\CriterionRegistrar;
use Bitrix\Main\Result;

class NullRegistrar extends CriterionRegistrar
{
	public function register(Data $data): Result
	{
		return new Result();
	}

	public function update(Data $data): Result
	{
		return new Result();
	}

	public function unregister(Data $data): Result
	{
		return new Result();
	}

	public function isNull(): bool
	{
		return true;
	}
}

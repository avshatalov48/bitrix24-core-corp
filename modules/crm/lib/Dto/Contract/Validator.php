<?php

namespace Bitrix\Crm\Dto\Contract;

use Bitrix\Main\Result;

interface Validator
{
	public function validate(array $fields): Result;
}

<?php

namespace Bitrix\Crm\Controller\Validator;

use Bitrix\Main\Result;

interface Validator
{
	public function validate(mixed $value): Result;
}

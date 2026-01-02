<?php

namespace Bitrix\Crm\Dto\Validator;

use Bitrix\Crm\Dto\Contract\Validator;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\Logic\LogicOr;

final class Logic
{
	private function __construct()
	{
	}

	public static function or(Dto $dto, array $validators): LogicOr
	{
		return new LogicOr($dto, $validators);
	}
}

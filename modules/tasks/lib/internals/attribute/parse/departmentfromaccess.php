<?php

namespace Bitrix\Tasks\Internals\Attribute\Parse;

use Attribute;
use Bitrix\Main\Access\AccessCode;
use Bitrix\Tasks\Internals\Attribute\ParserInterface;

#[Attribute]
class DepartmentFromAccess implements ParserInterface
{
	use ParserTrait;

	public function parse(mixed $value): array
	{
		return $this->parseValue($value, AccessCode::TYPE_DEPARTMENT);
	}
}
<?php

namespace Bitrix\Crm\Integration\AI\Function\UserField\Enum;

use Bitrix\Crm\Traits\Enum\HasValues;

enum UserFieldType: string
{
	use HasValues;

	case Double = 'double';
	case Integer = 'integer';
	case String = 'string';
	case Date = 'date';
	case DateTime = 'datetime';
	case Enumeration = 'enumeration';
	case File = 'file';
	case Boolean = 'boolean';

	public function id(): string
	{
		return $this->value;
	}
}

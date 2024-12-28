<?php

namespace Bitrix\HumanResources\Type\HcmLink;

use Bitrix\HumanResources\Trait\ValuesTrait;

enum FieldType: int
{
	use ValuesTrait;

	private const SPECIAL = 1024;

	case UNKNOWN = 0;
	case STRING = 1;
	case FIRST_NAME = 1025;
	case LAST_NAME = 1026;
	case PATRONYMIC_NAME = 1027;
	case PHONE = 1028;
	case EMAIL = 1029;
	case ADDRESS = 1030;
	case BIRTHDAY = 1031;
	case SNILS = 1032;
	case INN = 1033;
	case POSITION = 1034;
	case DEPARTMENT = 1035;

	public function isSpecial(): bool
	{
		return $this->value > self::SPECIAL;
	}
}

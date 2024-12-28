<?php

namespace Bitrix\HumanResources\Type;

use Bitrix\HumanResources\Trait\ValuesTrait;

enum FieldEntityType: string
{
	case TYPE_ADDRESS = 'address';
	case TYPE_STRING = 'string';
	case TYPE_INTEGER = 'integer';
	case TYPE_ENUMERATION = 'enumeration';

	use ValuesTrait;
}

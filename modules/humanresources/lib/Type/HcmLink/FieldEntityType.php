<?php

namespace Bitrix\HumanResources\Type\HcmLink;

use Bitrix\HumanResources\Internals\Trait\ValuesTrait;

enum FieldEntityType: int
{
	use ValuesTrait;

	case UNKNOWN = 0;
	case EMPLOYEE = 1;
	case COMPANY = 2;
	case DOCUMENT = 3;
}

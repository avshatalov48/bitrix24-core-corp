<?php

namespace Bitrix\HumanResources\Type\HcmLink;

use Bitrix\HumanResources\Trait\ValuesTrait;

enum FieldEntityType: int
{
	use ValuesTrait;

	case UNKNOWN = 0;
	case EMPLOYEE = 1;
	case COMPANY = 2;
}

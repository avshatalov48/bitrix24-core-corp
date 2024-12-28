<?php

namespace Bitrix\HumanResources\Type\HcmLink;

use Bitrix\HumanResources\Trait\ValuesTrait;

enum JobType: int
{
	use ValuesTrait;

	case UNKNOWN = 0;
	case COMPANY_LIST = 1;
	case USER_LIST = 2;
	case FIELD_VALUES = 3;
	case FIELDS = 4;
	case COMPLETE_MAPPING = 5;
}

<?php

namespace Bitrix\Sign\Type\Integration\HumanResources;

use Bitrix\Sign\Type\ValuesTrait;

enum FieldSerializedIndex: int
{
	use ValuesTrait;
	case FIELD_PREFIX_INDEX = 0;
	case FIELD_COMPANY_INDEX = 1;
	case FIELD_TYPE_INDEX = 2;
	case FIELD_PARTY_INDEX = 3;
	case FIELD_ID = 4;
}
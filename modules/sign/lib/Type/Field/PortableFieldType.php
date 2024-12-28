<?php

namespace Bitrix\Sign\Type\Field;

use Bitrix\Sign\Type\ValuesTrait;

enum PortableFieldType: string
{
	use ValuesTrait;

	case USER_FIELD = 'USER_FIELD';
}

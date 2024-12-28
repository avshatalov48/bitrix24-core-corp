<?php

namespace Bitrix\Sign\Type\Field;

use Bitrix\Sign\Type\ValuesTrait;

enum FrontFieldCategory: string
{
	use ValuesTrait;

	case PROFILE = 'PROFILE';
	case DYNAMIC_MEMBER = 'DYNAMIC_MEMBER';
}

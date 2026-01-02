<?php

namespace Bitrix\HumanResources\Type;

use Bitrix\HumanResources\Internals\Trait\ValuesTrait;

enum StructureType: string
{
	case DEFAULT = 'DEFAULT';
	case COMPANY = 'COMPANY';

	use ValuesTrait;
}

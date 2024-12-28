<?php

namespace Bitrix\HumanResources\Type;

use Bitrix\HumanResources\Trait\ValuesTrait;

enum AccessibleItemType: string
{
	case NODE = 'NODE';
	case NODE_MEMBER = 'NODE_MEMBER';

	use ValuesTrait;
}
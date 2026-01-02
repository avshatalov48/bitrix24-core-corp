<?php

namespace Bitrix\HumanResources\Type;

use Bitrix\HumanResources\Internals\Trait\ValuesTrait;

enum AccessibleItemType: string
{
	case NODE = 'NODE';
	case NODE_MEMBER = 'NODE_MEMBER';
	case USER = 'USER';
	case CHAT_LIST = 'CHAT_LIST';

	use ValuesTrait;
}

<?php

namespace Bitrix\HumanResources\Enum;

use Bitrix\HumanResources\Contract;

enum EventName implements Contract\Enum\EventName
{
	case MEMBER_ADDED;
	case MEMBER_UPDATED;
	case MEMBER_DELETED;
	case RELATION_ADDED;
	case RELATION_DELETED;
	case NODE_ADDED;
	case NODE_UPDATED;
	case NODE_DELETED;
}
<?php

namespace Bitrix\HumanResources\Enum;

enum EventName
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
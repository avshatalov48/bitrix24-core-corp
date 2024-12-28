<?php

namespace Bitrix\HumanResources\Type;

use Bitrix\HumanResources\Trait\ValuesTrait;

enum NodeEntityType: string
{
	case DEPARTMENT = 'DEPARTMENT';
	case GROUP = 'GROUP';
	case TEAM = 'TEAM';

	use ValuesTrait;
}

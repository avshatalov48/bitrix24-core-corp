<?php

namespace Bitrix\HumanResources\Type;

enum NodeEntityType: string
{
	case DEPARTMENT = 'DEPARTMENT';
	case GROUP = 'GROUP';
	case TEAM = 'TEAM';

	use ValuesTrait;
}

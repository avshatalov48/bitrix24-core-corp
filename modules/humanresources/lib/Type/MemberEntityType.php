<?php

namespace Bitrix\HumanResources\Type;

enum MemberEntityType: string
{
	case USER = 'USER';

	use ValuesTrait;
}

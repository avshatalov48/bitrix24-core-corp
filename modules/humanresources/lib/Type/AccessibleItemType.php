<?php

namespace Bitrix\HumanResources\Type;

enum AccessibleItemType: string
{
	case NODE = 'NODE';
	case NODE_MEMBER = 'NODE_MEMBER';

	use ValuesTrait;
}
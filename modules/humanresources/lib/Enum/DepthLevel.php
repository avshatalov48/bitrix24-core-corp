<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Enum;

enum DepthLevel
{
	case NONE;
	case FIRST;
	case FULL;
	case  WITHOUT_PARENT;
}
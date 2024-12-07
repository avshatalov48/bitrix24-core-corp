<?php

namespace Bitrix\Intranet\Enum;

enum DepartmentActiveFilter
{
	case ALL;
	case ONLY_ACTIVE;
	case ONLY_GLOBAL_ACTIVE;
}
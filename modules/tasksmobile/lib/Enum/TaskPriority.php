<?php

declare(strict_types=1);

namespace Bitrix\TasksMobile\Enum;

enum TaskPriority: int
{
	case Normal = 1;
	case High = 2;
}

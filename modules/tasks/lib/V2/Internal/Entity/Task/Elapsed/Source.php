<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\Task\Elapsed;

enum Source: string
{
	case Unknown = 'unknown';
	case System = 'system';
	case Manual = 'manual';
}
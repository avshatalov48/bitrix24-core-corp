<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\Task;

enum Mark: string
{
	case Positive = 'positive';
	case Negative = 'negative';
	case None = 'none';
}
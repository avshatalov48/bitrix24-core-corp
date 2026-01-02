<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\Task\Gantt;

enum LinkType: string
{
	case StartStart = 'start_start';
	case StartFinish = 'start_finish';
	case FinishStart = 'finish_start';
	case FinishFinish = 'finish_finish';
}

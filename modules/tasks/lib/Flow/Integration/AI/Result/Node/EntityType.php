<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Result\Node;

enum EntityType: string
{
	case TASK = 'task';
	case EMPLOYEE = 'employees';
	case CREATOR = 'creator';
	case FLOW = 'flow';
}

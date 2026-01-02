<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Provider;

enum CollectedDataStatus: string
{
	case COLLECTING = 'collecting';
	case COLLECTED = 'collected';
	case SUCCESS = 'success';
	case ERROR = 'error';
	case LIMIT_EXCEEDED = 'limit_exceeded';
}
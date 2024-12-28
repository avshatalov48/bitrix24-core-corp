<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Result\Node;

enum NodeType: string
{
	case AVERAGE = 'average';
	case PERCENTAGE = 'percentage';
	case MERGE = 'merge';
	case VALUE = 'value';
	case SUM = 'sum';
	case NESTED_SUM = 'nested_sum';
	case NESTED_VALUE = 'nested_value';
}

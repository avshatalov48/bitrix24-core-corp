<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type;

use Bitrix\Tasks\Flow\Integration\AI\Result\Node\NodeType;

class PercentageNode extends AverageNode
{
	public function getNodeType(): NodeType
	{
		return NodeType::PERCENTAGE;
	}

	public function getFinalResult(): float
	{
		return ($this->divisor === 0.0) ? 0 : ($this->dividend / $this->divisor * 100);
	}
}

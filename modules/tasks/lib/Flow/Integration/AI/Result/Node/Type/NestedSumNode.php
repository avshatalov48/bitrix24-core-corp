<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type;

use Bitrix\Tasks\Flow\Integration\AI\Result\Node\AbstractNestedNode;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\AbstractNode;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\NodeType;

class NestedSumNode extends AbstractNestedNode
{
	public function getNodeType(): NodeType
	{
		return NodeType::NESTED_SUM;
	}

	public function summarize(AbstractNode $node): static
	{
		if (!$node instanceof $this)
		{
			return $this;
		}

		$values = $node->getNestedValues();
		foreach ($values as $item)
		{
			$index = $item['identifier'] . '_' . $this->entity;
			if (isset($this->nestedValues[$index]))
			{
				$this->nestedValues[$index]['value'] += $item['value'];
			}
			else
			{
				$this->nestedValues[$index] = $item;
			}
		}

		return $this;
	}
}

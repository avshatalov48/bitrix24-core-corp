<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Result;

use ArrayIterator;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\AbstractNode;
use IteratorAggregate;

class CollectorResult implements IteratorAggregate
{
	/** @var AbstractNode[]  */
	protected array $nodes = [];

	public function addNode(AbstractNode $node): static
	{
		$node->addToArray($this->nodes);

		return $this;
	}

	/**
	 * @return AbstractNode[]
	 */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->nodes);
	}

	public function toArraySummarize(): array
	{
		$result = [];
		foreach ($this->nodes as $node)
		{
			$node->addToStepResult($result);
		}

		return $result;
	}

	public function toArrayFinalize(): array
	{
		$result = [];
		foreach ($this->nodes as $node)
		{
			$node->addToFinalResult($result);
		}

		return $result;
	}
}

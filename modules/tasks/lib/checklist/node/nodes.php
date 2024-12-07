<?php

namespace Bitrix\Tasks\CheckList\Node;

use ArrayIterator;
use Bitrix\Main\Type\Contract\Arrayable;
use Countable;
use IteratorAggregate;

class Nodes implements IteratorAggregate, Arrayable, Countable
{
	/** @var Node[]  */
	private array $nodes;

	public static function createFromArray(array|Arrayable $data): static
	{
		if ($data instanceof Arrayable)
		{
			$data = $data->toArray();
		}

		$nodes = [];
		foreach ($data as $nodeId => $item)
		{
			$nodes[] = Node::createFromArray($item)->setNodeId($nodeId);
		}

		return new static(...$nodes);
	}

	public function __construct(Node ...$nodes)
	{
		$this->nodes = $nodes;
	}

	/** @return Node[] */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->nodes);
	}

	public function toArray(): array
	{
		$nodes = [];
		foreach ($this->nodes as $node)
		{
			$nodes[$node->nodeId] = $node->toArray();
		}

		return $nodes;
	}

	public function count(): int
	{
		return count($this->nodes);
	}

	public function isEmpty(): bool
	{
		return 0 === $this->count();
	}

	public function getAuditors(): array
	{
		$auditors = [];
		foreach ($this->nodes as $node)
		{
			$auditors = array_merge($auditors, $node->getAuditors());
		}

		return array_unique($auditors);
	}

	public function getAccomplices(): array
	{
		$accomplices = [];
		foreach ($this->nodes as $node)
		{
			$accomplices = array_merge($accomplices, $node->getAccomplices());
		}

		return array_unique($accomplices);
	}

	public function validate(): void
	{
		foreach ($this->nodes as $node)
		{
			$node->validate();
		}
	}
}
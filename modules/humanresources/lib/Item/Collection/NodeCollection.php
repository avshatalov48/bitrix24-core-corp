<?php

namespace Bitrix\HumanResources\Item\Collection;

use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Type\NodeEntityType;
use SplQueue;

/**
 * @extends BaseCollection<Item\Node>
 */
class NodeCollection extends BaseCollection
{
	public function orderMapByInclude(): static
	{
		$sorted = [];
		$nodes = $this->itemMap;
		/** @var SplQueue<int> $queue */
		$queue = new SplQueue();
		$adjacencyList = [];

		foreach ($nodes as $node)
		{
			$parentId = (int)$node->parentId;
			if (!isset($adjacencyList[$parentId]))
			{
				$adjacencyList[$parentId] = [];
			}
			$adjacencyList[$parentId][] = $node->id;

			if ((int)$node->parentId === 0 || !isset($nodes[$node->parentId]))
			{
				$queue->enqueue($node->id);
			}
		}

		while (!$queue->isEmpty())
		{
			$nodeId = $queue->dequeue();
			$sorted[$nodeId] = $nodes[$nodeId];

			if (isset($adjacencyList[$nodeId]))
			{
				foreach ($adjacencyList[$nodeId] as $childId)
				{
					$queue->enqueue($childId);
				}
			}
		}

		$newNodeCollection = new static();
		$newNodeCollection->itemMap = $sorted;

		return $newNodeCollection;
	}

	final public function merge(self $nodeCollection): static
	{
		return new static(...$this, ...$nodeCollection);
	}

	final public function mergeWithUniqueItems(self $nodes): static
	{
		$new = clone $this;
		foreach ($nodes as $node)
		{
			if (!$new->containsNodeWithId($node->id))
			{
				$new->add($node);
			}
		}

		return $new;
	}

	/**
	 * @return list<int>
	 */
	final public function getIds(): array
	{
		return $this->getKeys();
	}

	/**
	 * @param array<NodeEntityType> $entityTypes
	 *
	 * @return $this
	 */
	public function filterByEntityTypes(NodeEntityType... $entityTypes): static
	{
		$new = new static();
		foreach ($this as $node)
		{
			if (in_array($node->type, $entityTypes, true))
			{
				$new->add($node);
			}
		}

		return $new;
	}

	public function containsNodeWithId(int $nodeId): bool
	{
		return isset($this->itemMap[$nodeId]);
	}

	public function containsNodeWithParentId(int $parentId): bool
	{
		foreach ($this as $node)
		{
			if ($node->parentId === $parentId)
			{
				return true;
			}
		}

		return false;
	}

	public function filterToUniqueNodeByParentId(): static
	{
		$new = new static();
		foreach ($this as $node)
		{
			if (!$new->containsNodeWithParentId($node->parentId))
			{
				$new->add($node);
			}
		}

		return $new;
	}

	public function getParentNodesFor(Item\Node $node): static
	{
		$parentNodes = new static();
		$parentId = $node->parentId;
		while ($parentId > 0)
		{
			$parentNode = $this->findFirstByRule(
				static fn(Item\Node $item): bool => $item->id === $parentId,
			);
			if ($parentNode === null)
			{
				break;
			}

			$parentNodes->add($parentNode);
			$parentId = $parentNode->parentId;
		}

		return $parentNodes;
	}

	public function getChildrenNodesFor(Item\Node $node): static
	{
		$childrenNodes = new static();
		$nodesToProcess = [$node->id];
		$processedNodes = [];

		while (!empty($nodesToProcess))
		{
			$currentNodeId = array_shift($nodesToProcess);

			// Skip already processed nodes to avoid infinite loops
			if (isset($processedNodes[$currentNodeId]))
			{
				continue;
			}

			$processedNodes[$currentNodeId] = true;

			$children = $this->filter(
				static fn(Item\Node $item): bool => $item->parentId === $currentNodeId,
			);

			if ($children->empty())
			{
				continue;
			}

			foreach ($children as $child)
			{
				if ($child->id !== $node->id)
				{
					$childrenNodes->add($child);
				}
				if (!isset($processedNodes[$child->id]))
				{
					$nodesToProcess[] = $child->id;
				}
			}
		}

		return $childrenNodes;
	}

	public function getFirstWithMaxDepth(): ?Item\Node
	{
		$maxDepth = 0;
		$foundNode = null;
		foreach ($this as $node)
		{
			if ($node->depth > $maxDepth)
			{
				$maxDepth = $node->depth;
				$foundNode = $node;
			}
		}

		return $foundNode;
	}

	final public function diff(self $nodes): static
	{
		$new = new static();
		foreach ($this as $node)
		{
			if (!$nodes->containsNodeWithId($node->id))
			{
				$new->add($node);
			}
		}

		return $new;
	}

	final public function isAncestor(Item\Node $ancestor, Item\Node $descendant): bool
	{
		return $this->getParentNodesFor($descendant)->containsNodeWithId($ancestor->id);
	}

	/**
	 * @param array<int> $departmentsWithSubteamsIds
	 *
	 * @return static
	 */
	final public function filterWithIncludedIds(array $departmentsWithSubteamsIds): static
	{
		$new = new static();
		foreach ($this as $node)
		{
			if (in_array($node->id, $departmentsWithSubteamsIds, true))
			{
				$new->add($node);
			}
		}

		return $new;
	}

	final public function getBottomNodes(): static
	{
		$bottomNodes = new static();
		foreach ($this as $node)
		{
			if (!$this->containsNodeWithParentId($node->id))
			{
				$bottomNodes->add($node);
			}
		}

		return $bottomNodes;
	}

	public function getNodesByIds(array $ids): static
	{
		return $this->filter(
			static fn(Item\Node $item): bool => in_array($item->id, $ids, true),
		);
	}

	public function intersect(self $nodes): static
	{
		$new = new static();
		foreach ($this as $node)
		{
			if ($nodes->containsNodeWithId($node->id))
			{
				$new->add($node);
			}
		}

		return $new;
	}
}

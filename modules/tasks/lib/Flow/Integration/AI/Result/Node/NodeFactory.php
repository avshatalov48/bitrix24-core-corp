<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Result\Node;

use Bitrix\Tasks\Flow\Integration\AI\Result\Node\Exception\CreateNodeException;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type\AverageNode;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type\MergeNode;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type\NestedSumNode;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type\NestedValueNode;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type\PercentageNode;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type\SumNode;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type\ValueNode;
use Closure;

class NodeFactory
{
	private string $path;
	private mixed $value;
	private NodeType $nodeType;
	private EntityType $entityType;
	private string $name;

	/**
	 * @throws CreateNodeException
	 */
	private function __construct(string $path, mixed $value)
	{
		$this->path = $path;

		$pathParts = explode('.', $this->path);
		if (count($pathParts) <= 2)
		{
			throw new CreateNodeException();
		}

		[$nodeType, $entityType, $this->name] = $pathParts;

		$this->nodeType = NodeType::from($nodeType);
		$this->entityType = EntityType::from($entityType);

		$this->value = $value;
	}

	/**
	 * @throws CreateNodeException
	 */
	public static function create(string $path, mixed $value): AbstractNode
	{
		$factory = new static($path, $value);
		$constructor = $factory->getConstructor();

		return $constructor();
	}

	private function getConstructor(): Closure
	{
		return match ($this->nodeType)
		{
			NodeType::AVERAGE => function () {
				$entity = $this->value['entity'] ?? 0;
				$dividend = $this->value['dividend'] ?? 0;
				$divisor = $this->value['divisor'] ?? 0;
				$group = $this->value['group'] ?? '';

				return new AverageNode($this->entityType, $this->name, $dividend, $divisor, $entity, $group);
			},

			NodeType::PERCENTAGE => function () {
				$entity = $this->value['entity'] ?? 0;
				$dividend = $this->value['dividend'] ?? 0;
				$divisor = $this->value['divisor'] ?? 0;
				$group = $this->value['group'] ?? '';

				return new PercentageNode($this->entityType, $this->name, $dividend, $divisor, $entity, $group);
			},

			NodeType::MERGE => function () {
				$entity = $this->value['entity'] ?? 0;
				$value = $this->value['value'] ?? [];
				$group = $this->value['group'] ?? '';

				return new MergeNode($this->entityType, $this->name, $value, $entity, $group);
			},

			NodeType::VALUE => function () {
				$entity = $this->value['entity'] ?? 0;
				$value = $this->value['value'] ?? 0;
				$group = $this->value['group'] ?? '';

				return new ValueNode($this->entityType, $this->name, $value, $entity, $group);
			},

			NodeType::SUM => function () {
				$entity = $this->value['entity'] ?? 0;
				$term = $this->value['term'] ?? 0;
				$group = $this->value['group'] ?? '';

				return new SumNode($this->entityType, $this->name, $term, $entity, $group);
			},

			NodeType::NESTED_SUM => function () {
				$entity = $this->value['entity'] ?? 0;
				$nestedValues = $this->value['nested_values'] ?? [];
				$group = $this->value['group'] ?? '';

				return new NestedSumNode($this->entityType, $this->name, $nestedValues, $entity, $group);
			},

			NodeType::NESTED_VALUE => function () {
				$entity = $this->value['entity'] ?? 0;
				$nestedValues = $this->value['nested_values'] ?? [];
				$group = $this->value['group'] ?? '';

				return new NestedValueNode($this->entityType, $this->name, $nestedValues, $entity, $group);
			},
		};
	}
}

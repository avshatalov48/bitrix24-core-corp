<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Result\Node;

abstract class AbstractNestedNode extends AbstractNode
{
	protected int $userId;
	protected int $term;
	protected array $nestedValues = [];

	final public function __construct(EntityType $entityType, string $name, array $nestedValues, mixed $entity = 0, string $group = '')
	{
		parent::__construct($entityType, $name, $entity, $group);

		foreach ($nestedValues as $item)
		{
			$index = $item['identifier'] . '_' . $this->entity;
			$this->nestedValues[$index] = [
				'value' => $item['value'],
				'identifier' =>  $item['identifier'],
			];
		}
	}

	public function getStepResult(): array
	{
		return [
			'entity' => $this->entity,
			'nested_values' => $this->nestedValues,
		];
	}

	public function getFinalResult(): array
	{
		$result = [];
		foreach ($this->nestedValues as $value)
		{
			$identifier = $value['identifier'];
			if (!empty($identifier))
			{
				$result[$identifier] = $value['value'];
			}
		}

		return $result;
	}

	public function addToArray(array &$result): void
	{
		$path = $this->getPath();

		$existingNode = $result[$path] ?? null;

		if ($existingNode instanceof $this)
		{
			$result[$path] = $existingNode->merge($this);

			return;
		}

		$result[$path] = $this;
	}

	public function merge(AbstractNestedNode $nestedNode): static
	{
		$this->nestedValues = array_merge($this->nestedValues, $nestedNode->getNestedValues());

		return $this;
	}

	public function getNestedValues(): array
	{
		return $this->nestedValues;
	}
}

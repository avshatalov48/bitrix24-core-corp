<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type;

use Bitrix\Tasks\Flow\Integration\AI\Result\Node\AbstractNode;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\EntityType;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\NodeType;

class MergeNode extends AbstractNode
{
	protected array $value;
	protected bool $isUnique = false;

	public function __construct(EntityType $entityType, string $name, array $value, mixed $entity = 0, string $group = '')
	{
		parent::__construct($entityType, $name, $entity, $group);

		$this->value = $value;
	}

	public function getNodeType(): NodeType
	{
		return NodeType::MERGE;
	}

	public function summarize(AbstractNode $node): static
	{
		if (!$node instanceof $this)
		{
			return $this;
		}

		$this->value = array_merge($this->value, $node->getValue());

		if ($this->isUnique)
		{
			$this->value = array_unique($this->value);
		}

		return $this;
	}


	public function getStepResult(): array
	{
		return [
			'value' => $this->value,
			'entity' => $this->entity,
		];
	}

	public function getFinalResult(): mixed
	{
		return $this->value;
	}

	public function getValue(): array
	{
		return $this->value;
	}

	public function setUnique(bool $isUnique = true): static
	{
		$this->isUnique = $isUnique;

		return $this;
	}
}

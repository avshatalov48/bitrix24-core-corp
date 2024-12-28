<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type;

use Bitrix\Tasks\Flow\Integration\AI\Result\Node\AbstractNode;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\EntityType;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\NodeType;

class ValueNode extends AbstractNode
{
	protected mixed $value;

	public function __construct(EntityType $entityType, string $name, mixed $value, mixed $entity = 0, string $group = '')
	{
		parent::__construct($entityType, $name, $entity, $group);

		$this->value = $value;
	}

	public function getNodeType(): NodeType
	{
		return NodeType::VALUE;
	}

	public function summarize(AbstractNode $node): static
	{
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

	public function getValue(): mixed
	{
		return $this->value;
	}
}

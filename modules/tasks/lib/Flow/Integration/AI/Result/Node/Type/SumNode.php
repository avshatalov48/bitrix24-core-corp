<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type;

use Bitrix\Tasks\Flow\Integration\AI\Result\Node\AbstractNode;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\EntityType;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\NodeType;

class SumNode extends AbstractNode
{
	protected mixed $term;

	public function __construct(EntityType $entityType, string $name, int|float $term, mixed $entity = 0, string $group = '')
	{
		parent::__construct($entityType, $name, $entity, $group);

		$this->term = $term;
	}

	public function getNodeType(): NodeType
	{
		return NodeType::SUM;
	}

	public function summarize(AbstractNode $node): static
	{
		if (!$node instanceof $this)
		{
			return $this;
		}

		$this->term += $node->getTerm();

		return $this;
	}

	public function getStepResult(): array
	{
		return [
			'term' => $this->term,
			'entity' => $this->entity,
			'group' => $this->group,
		];
	}

	public function getFinalResult(): int|float
	{
		return $this->term;
	}

	public function getTerm(): int|float
	{
		return $this->term;
	}
}
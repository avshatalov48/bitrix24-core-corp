<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type;

use Bitrix\Tasks\Flow\Integration\AI\Result\Node\AbstractNode;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\EntityType;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\NodeType;

class AverageNode extends AbstractNode
{
	protected float $dividend;
	protected float $divisor;

	public function __construct(EntityType $entityType, string $name, float $dividend, float $divisor, mixed $entity = 0, string $group = '')
	{
		parent::__construct($entityType, $name, $entity, $group);

		$this->dividend = $dividend;
		$this->divisor = $divisor;
	}

	public function getNodeType(): NodeType
	{
		return NodeType::AVERAGE;
	}

	public function summarize(AbstractNode $node): static
	{
		if (!$node instanceof $this)
		{
			return $this;
		}

		$this->dividend += $node->getDividend();
		$this->divisor += $node->getDivisor();

		return $this;
	}

	public function getStepResult(): array
	{
		return [
			'dividend' => $this->dividend,
			'divisor' => $this->divisor,
			'entity' => $this->entity,
		];
	}

	public function getFinalResult(): float
	{
		return ($this->divisor === 0.0) ? 0 : ($this->dividend / $this->divisor);
	}

	public function getDividend(): float
	{
		return $this->dividend;
	}

	public function getDivisor(): float
	{
		return $this->divisor;
	}
}

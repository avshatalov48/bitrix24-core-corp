<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Result\Node;

use Bitrix\Tasks\Flow\Integration\AI\Configuration;

abstract class AbstractNode
{
	protected EntityType $entityType;
	protected string $name;
	protected mixed $entity;
	protected string $group;

	public function __construct(EntityType $entityType, string $name, mixed $entity = 0, string $group = '')
	{
		$this->entityType = $entityType;
		$this->name = $name;
		$this->entity = $entity;
		$this->group = $group;
	}

	public function isMultiple(): bool
	{
		return !empty($this->entity);
	}

	public function isGrouped(): bool
	{
		return '' !== $this->group;
	}

	public function getGroup(): string
	{
		return $this->group;
	}

	abstract public function getNodeType(): NodeType;

	abstract public function summarize(self $node): static;

	abstract public function getStepResult(): mixed;

	abstract public function getFinalResult(): mixed;

	public function addToFinalResult(array &$result): void
	{
		$value = $this->getFinalResult();
		$value = $this->prepareValue($value);

		if ($this->isGrouped() && $this->isMultiple())
		{
			$result[$this->entityType->value][$this->group][$this->entity][$this->name] = $value;

			return;
		}
		if ($this->isGrouped() && !$this->isMultiple())
		{
			$result[$this->entityType->value][$this->group][$this->name] = $value;

			return;
		}

		if ($this->isMultiple())
		{
			$result[$this->entityType->value][$this->entity][$this->name] = $value;

			return;
		}

		$result[$this->entityType->value][$this->name] = $value;
	}

	public function addToStepResult(array &$result): void
	{
		$result[$this->getPath()] = $this->getStepResult();
	}

	public function addToArray(array &$result): void
	{
		$result[$this->getPath()] = $this;
	}

	public function getEntity(): int|string
	{
		return $this->entity;
	}

	public function getEntityType(): EntityType
	{
		return $this->entityType;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setEntity(int|string $entity): static
	{
		$this->entity = $entity;

		return $this;
	}

	protected function prepareValue(mixed $value): mixed
	{
		$value = $this->formatNumberRecursively($value);

		return $value;
	}

	protected function formatNumberRecursively(mixed $value): mixed
	{
		if (is_array($value))
		{
			array_walk_recursive($value, [$this, 'formatNumber']);
		}
		else
		{
			$this->formatNumber($value);
		}

		return $value;
	}

	protected function formatNumber(mixed &$value): void
	{
		if (is_int($value) || is_float($value))
		{
			$value = number_format($value, Configuration::getPrecisionOfValues());
		}
	}

	protected function getPath(): string
	{
		$path = $this->getNodeType()->value . '.' . $this->getEntityType()->value . '.' . $this->getName();
		if ($this->isMultiple())
		{
			$path .= '.' . $this->getEntity();
		}

		return $path;
	}
}
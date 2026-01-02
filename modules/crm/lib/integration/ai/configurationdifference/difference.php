<?php

namespace Bitrix\Crm\Integration\AI\ConfigurationDifference;

final class Difference
{
	private array $updated = [];
	private array $added = [];
	private array $removed = [];

	private const PRECISION = 2;

	public function __construct(
		private readonly int $defaultCount,
		private readonly int $actualCount,
	)
	{
	}

	public function addUpdated(int|string ...$id): self
	{
		$this->updated = array_merge($this->updated, $id);

		return $this;
	}

	public function updated(): array
	{
		return $this->updated;
	}

	public function addRemoved(int|string ...$id): self
	{
		$this->removed = array_merge($this->removed, $id);

		return $this;
	}

	public function removed(): array
	{
		return $this->removed;
	}

	public function addAdded(int|string ...$id): self
	{
		$this->added = array_merge($this->added, $id);

		return $this;
	}

	public function added(): array
	{
		return $this->added;
	}

	public function configuredPercentage(): float
	{
		if ($this->elementsCount() === 0)
		{
			return 0;
		}

		$configuredPercentage = ($this->changesCount() / $this->elementsCount()) * 100;

		return min(round($configuredPercentage, self::PRECISION), 100);
	}

	public function changesCount(): int
	{
		return count($this->updated) + count($this->removed) + count($this->added);
	}

	public function elementsCount(): int
	{
		return max($this->actualCount, $this->defaultCount);
	}
}

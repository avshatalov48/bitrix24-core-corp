<?php

namespace Bitrix\Crm\Integration\AI\ContextCollector\StagesQueryModificator;

class ExecutorResultValues
{
	/** @var array<string, ?int>  */
	private array $itemsCount = [];

	private ?int $defaultItemsCount = null;

	/** @var array<string, ?ItemsSum>  */
	private array $itemsSum = [];

	private ?ItemsSum $defaultItemsSum = null;

	public function getItemsSum(string $stageId): ?ItemsSum
	{
		return $this->itemsSum[$stageId] ?? $this->defaultItemsSum;
	}

	public function addItemsSum(string $stageId, ?ItemsSum $sum): self
	{
		$this->itemsSum[$stageId] = $sum;

		return $this;
	}

	public function setDefaultItemsSum(?ItemsSum $defaultItemsSum): self
	{
		$this->defaultItemsSum = $defaultItemsSum;

		return $this;
	}

	public function getItemsCount(string $stageId): ?int
	{
		return $this->itemsCount[$stageId] ?? $this->defaultItemsCount;
	}

	public function addItemsCount(string $stageId, ?int $count): self
	{
		$this->itemsCount[$stageId] = $count;

		return $this;
	}

	public function setDefaultItemsCount(?int $defaultItemsCount): self
	{
		$this->defaultItemsCount = $defaultItemsCount;

		return $this;
	}
}

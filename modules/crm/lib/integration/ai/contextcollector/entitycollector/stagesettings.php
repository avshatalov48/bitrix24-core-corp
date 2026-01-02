<?php

namespace Bitrix\Crm\Integration\AI\ContextCollector\EntityCollector;

final class StageSettings
{
	private bool $isCollect = true;
	private bool $isCollectItemsSum = true;
	private bool $isCollectItemsCount = true;

	public function isCollect(): bool
	{
		return $this->isCollect;
	}

	public function setIsCollect(bool $isCollect): self
	{
		$this->isCollect = $isCollect;

		return $this;
	}

	public function isCollectItemsSum(): bool
	{
		return $this->isCollectItemsSum;
	}

	public function setIsCollectItemsSum(bool $isCollectItemsSum): self
	{
		$this->isCollectItemsSum = $isCollectItemsSum;

		return $this;
	}

	public function isCollectItemsCount(): bool
	{
		return $this->isCollectItemsCount;
	}

	public function setIsCollectItemsCount(bool $isCollectItemsCount): self
	{
		$this->isCollectItemsCount = $isCollectItemsCount;

		return $this;
	}
}

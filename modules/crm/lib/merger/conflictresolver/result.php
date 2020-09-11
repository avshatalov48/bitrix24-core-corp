<?php

namespace Bitrix\Crm\Merger\ConflictResolver;

class Result extends \Bitrix\Main\Result
{
	protected $seeds = [];
	protected $target = [];
	protected $isTargetChanged = false;
	protected $historyItems = [];

	public function setFailed(): void
	{
		$this->isSuccess = false;
	}

	public function setSeedsValues(array $seeds): void
	{
		$this->seeds = $seeds;
	}

	public function setTargetValue(array $target): void
	{
		$this->target = $target;
	}

	public function setTargetChanged(bool $changed): void
	{
		$this->isTargetChanged = $changed;
	}

	public function setHistoryItems(array $historyItems): void
	{
		$this->historyItems = $historyItems;
	}

	public function isTargetChanged(): bool
	{
		return $this->isTargetChanged;
	}

	public function updateTarget(array &$target): void
	{
		$target = array_merge($target, $this->target);
	}

	public function updateSeed(int $seedId, array &$seed): void
	{
		if (array_key_exists($seedId, $this->seeds))
		{
			$seed = array_merge($seed, $this->seeds[$seedId]);
		}
	}

	public function getHistoryItems(): array
	{
		return $this->historyItems;
	}
}
<?php

namespace Bitrix\Crm\Merger\ConflictResolver;

class Base
{
	/** @var string */
	protected $fieldId;

	/** @var array */
	protected $seeds;
	/** @var array */
	protected $target;

	/** @var int */
	protected $curSeedId;

	/** @var bool */
	protected $isTargetChanged = false;

	/** @var array */
	protected $newTargetValue = [];

	/** @var array */
	protected $newSeedsValue = [];

	/** @var array */
	protected $historyItems = [];

	public function __construct(string $fieldId)
	{
		$this->fieldId = $fieldId;
	}

	public function setTarget(array $target): void
	{
		$this->target = $target;
	}

	public function addSeed(int $seedId, array $seed): void
	{
		$this->seeds[$seedId] = $seed;
	}

	public function resolve(): Result
	{
		$this->isTargetChanged = false;

		if (empty($this->seeds))
		{
			return new Result();
		}

		$result = new Result();
		if ($this->doResolve())
		{
			$result->setSeedsValues($this->newSeedsValue);
			$result->setTargetValue($this->getNewTargetValue());
			$result->setTargetChanged($this->isTargetChanged());
			$result->setHistoryItems($this->getHistoryItems());
		}
		else
		{
			$result->setFailed();
		}
		return $result;
	}

	protected function doResolve(): bool
	{
		foreach (array_keys($this->seeds) as $seedId)
		{
			$this->curSeedId = $seedId;
			$targetValue = $this->getTargetValue();
			$seedValue = $this->getSeedValue();

			if (!$this->resolveByValue($seedValue, $targetValue))
			{
				return false;
			}
		}

		return true;
	}

	protected function getSeed()
	{
		if ($this->curSeedId === null)
		{
			throw new \Bitrix\Main\ArgumentNullException('Current seed id');
		}
		return $this->seeds[$this->curSeedId] ?? null;
	}

	protected function getTarget()
	{
		return $this->target;
	}

	protected function resolveByValue(&$seedValue, &$targetValue): bool
	{
		return $seedValue == $targetValue;
	}

	protected function getSeedValue()
	{
		if ($this->curSeedId === null)
		{
			throw new \Bitrix\Main\ArgumentNullException('Current seed id');
		}
		return ($this->seeds[$this->curSeedId][$this->fieldId] ?? null);
	}

	protected function getTargetValue()
	{
		return $this->target[$this->fieldId] ?? null;
	}

	protected function isTargetChanged(): bool
	{
		return $this->isTargetChanged;
	}

	protected function getNewTargetValue(): array
	{
		if ($this->isTargetChanged())
		{
			return $this->newTargetValue;
		}
		return [];
	}

	protected function setNewTargetValue($newTargetValue, $fieldId = null): void
	{
		if ($fieldId === null)
		{
			$fieldId = $this->fieldId;
			$this->isTargetChanged = true;
		}
		$this->newTargetValue[$fieldId] = $newTargetValue;
	}

	protected function setNewSeedValue($newSeedValue, $fieldId = null): void
	{
		if ($fieldId === null)
		{
			$fieldId = $this->fieldId;
		}
		if ($this->curSeedId === null)
		{
			throw new \Bitrix\Main\ArgumentNullException('Current seed id');
		}

		$this->newSeedsValue[$this->curSeedId][$fieldId] = $newSeedValue;
	}

	protected function getHistoryItems(): array
	{
		return $this->historyItems;
	}

	protected function addHistoryItem(string $fieldId, string $value): void
	{
		if (!array_key_exists($fieldId, $this->historyItems))
		{
			$this->historyItems[$fieldId] = [];
		}
		$this->historyItems[$fieldId][] = $value;
	}
}
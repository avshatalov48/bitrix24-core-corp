<?php


namespace Bitrix\Crm\Merger\ConflictResolver;


class IgnoredField extends Base
{
	protected $needWriteToHistory = false;
	protected $needUpdateTargetIfEmpty = false;

	public function resolve(): Result
	{
		if ($this->needWriteToHistory || $this->needUpdateTargetIfEmpty)
		{
			return parent::resolve();
		}
		// in simple cases just resolve successfully
		return new Result();
	}

	protected function resolveByValue(&$seedValue, &$targetValue): bool
	{
		if ($seedValue != $targetValue && !empty($seedValue))
		{
			$newTarget = $this->getNewTargetValue();
			$newTargetValue = $newTarget[$this->fieldId] ?? '';
			if (empty($newTargetValue) && empty($targetValue) && $this->needUpdateTargetIfEmpty)
			{
				$this->setNewTargetValue($seedValue);
			}
			elseif ($newTargetValue != $seedValue && $this->needWriteToHistory)
			{
				$this->addHistoryItem($this->fieldId, $this->getHistoryItem($seedValue));
			}
		}
		return true;
	}

	public function setNeedUpdateTargetIfEmpty(bool $needUpdate): void
	{
		$this->needUpdateTargetIfEmpty = $needUpdate;
	}

	public function setNeedWriteToHistory(bool $needWrite): void
	{
		$this->needWriteToHistory = $needWrite;
	}

	protected function getHistoryItem($value): string
	{
		return (string)$value;
	}
}
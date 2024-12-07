<?php


namespace Bitrix\Crm\Merger\ConflictResolver;


class StringField extends Base
{
	protected $emptyValues = [];

	public function setEmptyValues(array $values): void
	{
		$this->emptyValues = $values;
	}

	protected function resolveByValue(&$seedValue, &$targetValue): bool
	{
		if (parent::resolveByValue($seedValue, $targetValue))
		{
			return true;
		}
		if ($this->isEmpty($seedValue))
		{
			return true;
		}
		if ($this->isEmpty($targetValue))
		{	// if target is empty, just use seed value
			$this->setNewTargetValue($seedValue);
			return true;
		}

		if ($this->checkTrimmed($seedValue, $targetValue))
		{
			return true;
		}

		if ($this->checkRedundantSpaces($seedValue, $targetValue))
		{
			return true;
		}

		if ($this->checkLetterCase($seedValue, $targetValue))
		{
			return true;
		}

		return false;
	}

	protected function isEmpty($value): bool
	{
		return in_array($value, $this->emptyValues);
	}

	protected function getSeedValue(): string
	{
		return (string)parent::getSeedValue();
	}

	protected function getTargetValue(): string
	{
		return (string)parent::getTargetValue();
	}

	protected function checkTrimmed(&$seedValue, &$targetValue): bool
	{
		$seedValue = trim($seedValue);
		$targetValue = trim($targetValue);

		if ($seedValue === $targetValue)
		{
			$this->setNewTargetValueIfNeed($seedValue);
			return true;
		}

		// check if difference is in dot at the end
		$seedValue = rtrim($seedValue, '.');
		$targetValue = rtrim($targetValue, '.');
		if ($seedValue === $targetValue)
		{
			$this->setNewTargetValueIfNeed($seedValue);
			return true;
		}

		if ($seedValue === '')
		{
			return true;
		}

		if ($targetValue === '')
		{
			$this->setNewTargetValue($this->getSeedValue());
			return true;
		}

		return false;
	}

	public function checkRedundantSpaces(&$seedValue, &$targetValue): bool
	{
		$formattedSeedValue = $this->removeSpaces($seedValue);
		$formattedTargetValue = $this->removeSpaces($targetValue);

		if ($formattedSeedValue !== null && $formattedTargetValue !== null)
		{
			$seedValue = $formattedSeedValue;
			$targetValue = $formattedTargetValue;

			if ($seedValue === $targetValue)
			{
				$this->setNewTargetValueIfNeed($seedValue);
				return true;
			}
		}

		return false;
	}

	protected function checkLetterCase(&$seedValue, &$targetValue): bool
	{
		$formattedSeedValue = mb_strtolower($seedValue);
		$formattedTargetValue = mb_strtolower($targetValue);

		if ($formattedSeedValue === $formattedTargetValue)
		{
			if ($formattedTargetValue === $targetValue)
			{
				// if target was completely in lowercase, will use seed instead
				// because seed is possibly not completely lower cased
				$this->setNewTargetValueIfNeed($seedValue);
			}

			return true;
		}

		return false;
	}

	protected function setNewTargetValueIfNeed($newValue): void
	{
		$oldSeedValue = $this->getSeedValue();

		if ($newValue === $oldSeedValue)
		{
			// only if $newValue equals to original seed value
			$this->setNewTargetValue($newValue);
		}
	}

	protected function removeSpaces(string $value): string
	{
		return preg_replace("/[ ]+/u", " ", $value);
	}
}
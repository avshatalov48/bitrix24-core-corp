<?php


namespace Bitrix\Crm\Merger\ConflictResolver;


use Bitrix\Main\Type\DateTime;

class TextField extends StringField
{
	protected function resolveByValue(&$seedValue, &$targetValue): bool
	{
		if (parent::resolveByValue($seedValue, $targetValue))
		{
			return true;
		}

		if (mb_stripos($targetValue, $seedValue) !== false)
		{
			return true;
		}

		// always be resolved successfully
		$this->setNewTargetValue($this->getMergedValue($targetValue, $seedValue));
		return true;
	}

	protected function getTargetValue(): string
	{
		if ($this->isTargetChanged())
		{
			return (string)($this->newTargetValue[$this->fieldId] ?? parent::getTargetValue());
		}
		return parent::getTargetValue();
	}

	protected function getMergedValue(string $targetValue, string $seedValue): string
	{
		if ($targetValue === '')
		{
			return $seedValue;
		}
		return $targetValue . $this->getSeparator() . $seedValue;
	}

	protected function getSeparator(): string
	{
		$newLine = $this->getNewLine();
		$context = \Bitrix\Main\Application::getInstance()->getContext();
		$culture = $context ? $context->getCulture() : null;
		if ($culture)
		{
			$date = new DateTime();
			$date->toUserTime();
			$dateStr = $date->format($culture->getShortDateFormat() . ' ' . $culture->getShortTimeFormat());
		}
		else
		{
			$dateStr = (new DateTime())->toString();
		}
		return $newLine . $newLine . ' ----- ' . $dateStr . ' -----' . $newLine;
	}

	protected function getNewLine(): string
	{
		return "\n";
	}
}
<?php

namespace Bitrix\BIConnector\ExternalSource\Validation\Rules;

final class DoubleIsNumericRule extends Rule
{
	private string $delimiter;

	public function __construct(string $delimiter)
	{
		parent::__construct();
		$this->delimiter = $delimiter;
	}

	public function validate($value): RuleValidationResult
	{
		$stringValue = (string)$value;

		if ($stringValue === '')
		{
			return new RuleValidationResult();
		}

		$normalizedValue = str_replace($this->delimiter, '.', $stringValue);

		if (!is_numeric($normalizedValue))
		{
			return new RuleValidationResult(false, str_replace('#VALUE#', $this->getValueForError($value), $this->invalidErrorTemplate));
		}

		if ($this->delimiter !== '.' && str_contains($stringValue, '.'))
		{
			return new RuleValidationResult(false, str_replace('#VALUE#', $this->getValueForError($value), $this->invalidErrorTemplate));
		}

		return new RuleValidationResult();
	}
}

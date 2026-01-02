<?php

namespace Bitrix\BIConnector\ExternalSource\Validation\Rules;

final class MoneyRule extends Rule
{
	private string $delimiter;

	public function __construct(string $delimiter)
	{
		parent::__construct();
		$this->delimiter = $delimiter;
	}

	public function validate($value): RuleValidationResult
	{
		$number = preg_replace('/[^-\d' . preg_quote($this->delimiter, '/') . ']/', '', $value);
		if ($this->delimiter !== '.')
		{
			$number = str_replace($this->delimiter, '.', $number);
		}

		$stringValue = (string)$number;

		if ($stringValue === '')
		{
			return new RuleValidationResult();
		}

		if (mb_substr_count($stringValue, '.') > 1)
		{
			return new RuleValidationResult(false, str_replace('#VALUE#', $this->getValueForError($value), $this->invalidErrorTemplate));
		}

		return new RuleValidationResult();
	}
}

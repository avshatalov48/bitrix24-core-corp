<?php

namespace Bitrix\BIConnector\ExternalSource\Validation\Rules;

final class IntegerIsNumericRule extends Rule
{
	public function validate($value): RuleValidationResult
	{
		if (empty($value))
		{
			return new RuleValidationResult();
		}

		if ((string)(int)$value !== (string)$value)
		{
			return new RuleValidationResult(false, str_replace('#VALUE#', $this->getValueForError($value), $this->invalidErrorTemplate));
		}

		return new RuleValidationResult();
	}
}

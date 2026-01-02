<?php

namespace Bitrix\BIConnector\ExternalSource\Validation\Rules;

use Bitrix\Main\Localization\Loc;

final class DateIsCorrectFormatRule extends Rule
{
	private string $format;
	private string $errorTemplate;

	public function __construct(string $format)
	{
		parent::__construct();
		$this->format = $format;
		$this->errorTemplate = Loc::getMessage('BICONNECTOR_DATASET_VALIDATION_INVALID_DATE') ?? '';
	}

	public function validate($value): RuleValidationResult
	{
		if (empty($value))
		{
			return new RuleValidationResult();
		}

		$dateTime = \DateTime::createFromFormat($this->format, $value);

		if ($dateTime === false)
		{
			return new RuleValidationResult(false, str_replace('#VALUE#', $this->getValueForError($value), $this->invalidErrorTemplate));
		}

		$errors = \DateTime::getLastErrors();

		if ($errors['error_count'] > 0)
		{
			return new RuleValidationResult(false, str_replace('#VALUE#', $this->getValueForError($value), $this->invalidErrorTemplate));
		}

		if ($errors['warning_count'] > 0)
		{
			return new RuleValidationResult(false, str_replace('#VALUE#', $this->getValueForError($value), $this->errorTemplate));
		}

		return new RuleValidationResult();
	}
}

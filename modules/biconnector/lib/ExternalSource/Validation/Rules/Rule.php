<?php

namespace Bitrix\BIConnector\ExternalSource\Validation\Rules;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

abstract class Rule
{
	protected string $invalidErrorTemplate;

	public function __construct()
	{
		$this->invalidErrorTemplate = Loc::getMessage('BICONNECTOR_DATASET_VALIDATION_INVALID_VALUE') ?? '';
	}

	abstract public function validate($value): RuleValidationResult;

	protected function getValueForError($value): string
	{
		return mb_strimwidth((string)$value, 0, 140, '...');
	}
}

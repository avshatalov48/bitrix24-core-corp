<?php

namespace Bitrix\BIConnector\ExternalSource\Validation;

use Bitrix\BIConnector\ExternalSource\FieldType;
use Bitrix\BIConnector\ExternalSource\Validation\Rules\Rule;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

final class ImportDataValidator
{
	/** @var array<value-of<FieldType>, array<Rule>> $rulesMap */
	private array $rulesMap;
	private array $fieldsSettings;

	public function __construct(array $rulesMap, array $fieldsSettings)
	{
		$this->rulesMap = $rulesMap;
		$this->fieldsSettings = $fieldsSettings;
	}

	public function validateRow(array $row): Result
	{
		$result = new Result();

		foreach ($row as $index => $value)
		{
			$fieldType = $this->fieldsSettings[$index]['TYPE'] ?? null;
			if (!$fieldType)
			{
				continue;
			}

			foreach ($this->rulesMap[$fieldType] ?? [] as $rule)
			{
				$ruleResult = $rule->validate($value);
				if (!$ruleResult->isSuccess)
				{
					$result->addError(new Error($ruleResult->message, customData: ['field' => $index]));
				}
			}
		}

		return $result;
	}
}

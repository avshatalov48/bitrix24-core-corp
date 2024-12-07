<?php

namespace Bitrix\Crm\Controller\Validator;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

final class Filter implements Validator
{
	private const CORRECT_FILTER_FIELD_PREFIXES = [
		'' => '',
		'=' => '=',
		'%' => '%',
		'>' => '>',
		'<' => '<',
		'@' => '@',
		'!' => '!',
		'!@' => '!@',
		'!=' => '!=',
		'!%' => '!%',
		'><' => '><',
		'>=' => '>=',
		'<=' => '<=',
		'=%' => '=%',
		'%=' => '%=',
		'!><' => '!><',
		'!=%' => '!=%',
		'!%=' => '!%=',
	];

	private const PREFIXES_THAT_SUPPORT_ARRAY_VALUES = [
		'@' => '@',
		'!@' => '!@',
		'!=' => '!=',
		'=' => '=',
		'' => '',
	];

	private array $allAllowedFilterKeys;

	public function __construct(array $allowedFields)
	{
		$this->allAllowedFilterKeys = [
			'logic' => 'logic',
			'LOGIC' => 'LOGIC',
		];

		foreach ($allowedFields as $fieldName)
		{
			foreach (self::CORRECT_FILTER_FIELD_PREFIXES as $prefix)
			{
				$filterFieldName = $prefix . $fieldName;

				$this->allAllowedFilterKeys[$filterFieldName] = $filterFieldName;
			}
		}
	}

	public static function isCorrectFilterFieldName(string $filterFieldName, string $fieldName): bool
	{
		$prefix = str_replace($fieldName, '', $filterFieldName);

		return isset(self::CORRECT_FILTER_FIELD_PREFIXES[$prefix]);
	}

	public function validate(mixed $value): Result
	{
		$result = new Result();

		$this->doValidate($value, $result);

		return $result;
	}

	private function doValidate(mixed $filter, Result $result): void
	{
		if (!is_array($filter))
		{
			$result->addError(new Error(
				'filter should be an associative array',
				ErrorCode::INVALID_ARG_VALUE
			));

			return;
		}

		foreach ($filter as $filterFieldName => $filterValue)
		{
			if (is_array($filterValue))
			{
				$this->doValidate($filterValue, $result);
			}

			if (
				!isset($this->allAllowedFilterKeys[$filterFieldName])
				&& !is_numeric($filterFieldName)
			)
			{
				$result->addError(
					new Error(
						"Invalid filter: field '{$filterFieldName}' is not allowed in filter",
						ErrorCode::INVALID_ARG_VALUE,
					)
				);
			}

			if (!$this->isAllowedValueTypeForKey($filterFieldName, $filterValue))
			{
				$result->addError(
					new Error(
						"Invalid filter: field '{$filterFieldName}' has invalid value",
						ErrorCode::INVALID_ARG_VALUE,
					)
				);
			}
		}
	}

	private function isAllowedValueTypeForKey(string $filterFieldName, mixed $filterValue): bool
	{
		$prefix = $this->extractPrefix($filterFieldName);
		if (is_string($prefix) && isset(self::PREFIXES_THAT_SUPPORT_ARRAY_VALUES[$prefix]) && is_array($filterValue))
		{
			return true;
		}

		return is_string($filterValue) || is_numeric($filterValue) || is_null($filterValue) || is_bool($filterValue);
	}

	private function extractPrefix(string $filterFieldName): ?string
	{
		if (!preg_match('/^([=%><@!]*)\w+$/', $filterFieldName, $matches))
		{
			return null;
		}

		$prefix = $matches[1];

		if (!isset(self::CORRECT_FILTER_FIELD_PREFIXES[$prefix]))
		{
			return null;
		}

		return $prefix;
	}
}

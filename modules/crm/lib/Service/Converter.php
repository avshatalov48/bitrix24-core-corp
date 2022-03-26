<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\Service\Converter\CaseCache;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Engine\Response;

abstract class Converter
{
	protected $upperConverter;
	protected $camelConverter;

	protected const UNDERSCORE_MARK = '~~~';

	/**
	 * Generates $model representation in json format.
	 *
	 * @param $model
	 * @return array
	 */
	abstract public function toJson($model): array;

	/**
	 * Upper Case is the main format for fieldNames.
	 * To avoid conflicts during camel to upper conversion this method should be called first on array of field names.
	 *
	 * @param array $fieldNames
	 * @return $this
	 */
	public function preprocessUpperFieldNames(array $fieldNames): self
	{
		CaseCache::getInstance()->clear();

		foreach ($fieldNames as $fieldName)
		{
			if (is_string($fieldName))
			{
				$this->convertFieldNameFromUpperCaseToCamelCase($fieldName);
			}
		}

		return $this;
	}

	/**
	 * Converts all keys recursively in $data to camelCase.
	 *
	 * @param array $data
	 * @return array
	 */
	public function convertKeysToCamelCase(array $data): array
	{
		$result = [];

		foreach ($data as $name => $value)
		{
			$name = $this->convertFieldNameFromUpperCaseToCamelCase($name);

			if (is_array($value))
			{
				$result[$name] = $this->convertKeysToCamelCase($value);
			}
			else
			{
				$result[$name] = $value;
			}
		}

		return $result;
	}

	/**
	 * Convert all keys in recursively in $data to UPPER_CASE.
	 *
	 * @param array $data
	 * @return array
	 */
	public function convertKeysToUpperCase(array $data): array
	{
		$result = [];

		foreach ($data as $name => $value)
		{
			$name = $this->convertFieldNameFromCamelCaseToUpperCase($name);

			if (is_array($value))
			{
				$result[$name] = $this->convertKeysToUpperCase($value);
			}
			else
			{
				$result[$name] = $value;
			}
		}

		return $result;
	}

	/**
	 * Converts $fieldName from camelCase to UPPER_CASE.
	 *
	 * @param string $fieldName
	 * @return string
	 */
	public function convertFieldNameFromCamelCaseToUpperCase(string $fieldName): string
	{
		// if there is camelCase for this $fieldName - than this $fieldName is already in UPPER_CASE.
		$camelCase = CaseCache::getInstance()->getCamelCase($fieldName);
		if ($camelCase)
		{
			return $fieldName;
		}

		$upperCase = CaseCache::getInstance()->getUpperCase($fieldName);
		if ($upperCase)
		{
			return $upperCase;
		}

		$upperCase = $this->getToUpperCaseConverter()->process($fieldName);

		CaseCache::getInstance()->add($fieldName, $upperCase);

		return $upperCase;
	}

	/**
	 * Converts $fieldName from UPPER_CASE to camelCase.
	 *
	 * @param string $fieldName
	 * @return string
	 */
	public function convertFieldNameFromUpperCaseToCamelCase(string $fieldName): string
	{
		$camelCase = CaseCache::getInstance()->getCamelCase($fieldName);
		if ($camelCase)
		{
			return $camelCase;
		}

		$camelCase = preg_replace(
			'/(\d)(_)(\d)/',
			'${1}' . static::UNDERSCORE_MARK . '${3}',
			$fieldName
		);
		$camelCase = $this->getToCamelCaseConverter()->process($camelCase);
		$camelCase = preg_replace(
			'/(\d)(' . static::UNDERSCORE_MARK . ')(\d)/',
			'${1}_${3}',
			$camelCase
		);

		// there is already such a fieldName for another field
		if (CaseCache::getInstance()->getUpperCase($camelCase))
		{
			$camelCase = $fieldName;
		}

		CaseCache::getInstance()->add($camelCase, $fieldName);

		return $camelCase;
	}

	protected function getToUpperCaseConverter(): Response\Converter
	{
		if (!$this->upperConverter)
		{
			$this->upperConverter = new Response\Converter(
				Response\Converter::TO_UPPER
				| Response\Converter::TO_SNAKE_DIGIT
			);
		}

		return $this->upperConverter;
	}

	protected function getToCamelCaseConverter(): Response\Converter
	{
		if (!$this->camelConverter)
		{
			$this->camelConverter = new Response\Converter(
				Response\Converter::TO_CAMEL
				| Response\Converter::LC_FIRST
			);
		}

		return $this->camelConverter;
	}

	protected function prepareData(array $data): array
	{
		$result = [];

		$data = $this->removeMultipleUserFieldValuesWithSingleSuffix($data);

		foreach ($data as $name => $value)
		{
			if (is_array($value))
			{
				$result[$name] = $this->prepareData($value);
			}
			elseif ($value instanceof Date)
			{
				$result[$name] = $this->processDate($value);
			}
			elseif (is_bool($value) && Container::getInstance()->getContext()->getScope() === Context::SCOPE_REST)
			{
				$result[$name] = $value ? 'Y' : 'N';
			}
			else
			{
				$result[$name] = $value;
			}
		}

		return $result;
	}

	protected function processDate(Date $date): string
	{
		if (
			Container::getInstance()->getContext()->getScope() === Context::SCOPE_REST
			&& Loader::includeModule('rest')
		)
		{
			if ($date instanceof DateTime)
			{
				return \CRestUtil::ConvertDateTime($date);
			}
			if ($date instanceof Date)
			{
				return \CRestUtil::ConvertDate($date);
			}
		}

		return $date->toString();
	}

	protected function removeMultipleUserFieldValuesWithSingleSuffix(array $data): array
	{
		$result = [];

		foreach ($data as $fieldName => $value)
		{
			if(
				mb_substr($fieldName, 0, 3) === 'UF_'
				&& mb_substr($fieldName, -7) === '_SINGLE'
			)
			{
				continue;
			}

			$result[$fieldName] = $value;
		}

		return $result;
	}
}

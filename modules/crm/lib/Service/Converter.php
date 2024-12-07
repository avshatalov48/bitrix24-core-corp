<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\Service\Converter\CaseCache;
use Bitrix\Main\Engine\Response;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

abstract class Converter
{
	protected ?Response\Converter $upperConverter = null;
	protected ?Response\Converter $camelConverter = null;

	protected const UNDERSCORE_MARK = '~~~';

	private CaseCache $caseCache;

	public function __construct(CaseCache $caseCache = null)
	{
		$this->caseCache = $caseCache !== null
			? $caseCache
			: Container::getInstance()->getConverterCaseCache();

	}

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
		$this->caseCache->clear();

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
		$camelCase = $this->caseCache->getCamelCase($fieldName);
		if ($camelCase)
		{
			return $fieldName;
		}

		$upperCase = $this->caseCache->getUpperCase($fieldName);
		if ($upperCase)
		{
			return $upperCase;
		}


		if ($this->isSpecialDecodeWayFromCamelToUpper($fieldName))
		{
			if (preg_match('/^ufCrm_/i', $fieldName))
			{
				$upperCase = 'UF_CRM_' . mb_substr($fieldName, 6);
			}
			else if (preg_match('/^ufCrm/i', $fieldName))
			{
				$upperCase = 'UF_CRM' . mb_substr($fieldName, 5);
			}
			else
			{
				$upperCase = 'UF_' . mb_substr($fieldName, 2);
			}
		}
		else
		{
			$upperCase = $this->getToUpperCaseConverter()->process($fieldName);
		}

		$this->caseCache->add($fieldName, $upperCase);

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

		$camelCase = $this->caseCache->getCamelCase($fieldName);
		if ($camelCase)
		{
			return $camelCase;
		}

		if ($this->isSpecialDecodeWayFromUpperToCamel($fieldName))
		{
			if (preg_match('/^UF_CRM_/i', $fieldName))
			{
				$camelCase = 'ufCrm_' . mb_substr($fieldName, 7);
			}
			else if (preg_match('/^UF_CRM/i', $fieldName))
			{
				$camelCase = 'ufCrm' . mb_substr($fieldName, 6);
			}
			else
			{
				$camelCase = 'uf' . mb_substr($fieldName, 3);
			}
		}
		else
		{
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
		}

		// there is already such a fieldName for another field
		if ($this->caseCache->getUpperCase($camelCase))
		{
			$camelCase = $fieldName;
		}

		$this->caseCache->add($camelCase, $fieldName);

		return $camelCase;
	}


	/**
	 * This complex check appeared because the situation was that alphanumeric User fields were converted without
	 * the possibility to get the same value during the reverse conversion.
	 * Therefore, for such fields it uses a "simplified" conversion.
	 * But since all-literal fields like "UF_CRM_MY_STRING" worked, we had to support the old conversion
	 * method for them.
	 * @link http://jabber.bx/view.php?id=165477
	 * @param string $fieldName
	 * @return bool
	 */
	private function isSpecialDecodeWayFromCamelToUpper(string $fieldName): bool
	{
		if (
			preg_match('/^[A-Za-z_]+$/', $fieldName)
			|| preg_match('/^ufCrm\d{1,4}_[\d_]+$/', $fieldName)
			|| preg_match('/^ufCrm\d{1,4}[A-Za-z_]+$/', $fieldName)
		)
		{
			return false;
		}
		else if (preg_match('/^uf/i', $fieldName))
		{
			return true;
		}
		return false;
	}

	/**
	 * @param string $fieldName
	 * @return bool
	 * @see isSpecialDecodeWayFromCamelToUpper
	 */
	private function isSpecialDecodeWayFromUpperToCamel(string $fieldName): bool
	{
		if (
			preg_match('/^[A-Z_]+$/', $fieldName)
			|| preg_match('/^UF_CRM_\d{1,4}_[\d_]+$/', $fieldName)
			|| preg_match('/^UF_CRM_\d{1,4}_[A-Z_]+$/', $fieldName)
		)
		{
			return false;
		}
		else if (preg_match('/^UF_/i', $fieldName))
		{
			return true;
		}
		return false;
	}

	protected function getToUpperCaseConverter(): Response\Converter
	{
		if ($this->upperConverter === null)
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

	/**
	 * Serialize data. Mainly handling of REST special cases
	 */
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
			elseif (is_bool($value) && $this->isRest())
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
			$this->isRest()
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

	final protected function isRest(): bool
	{
		return Container::getInstance()->getContext()->getScope() === Context::SCOPE_REST;
	}
}

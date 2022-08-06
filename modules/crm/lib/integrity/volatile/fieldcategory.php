<?php

namespace Bitrix\Crm\Integrity\Volatile;

use Bitrix\Crm\EntityRequisite;
use Bitrix\Main\Localization\Loc;
use CCrmFieldMulti;

class FieldCategory
{
	public const UNDEFINED = 0;
	public const ENTITY = 1;
	public const MULTI = 2;
	public const ADDRESS = 3;
	public const REQUISITE = 4;
	public const BANK_DETAIL = 5;

	public const PREFIX_MULTI = 'FM';
	public const PREFIX_REQUISITE = 'RQ';
	public const PREFIX_BANK_DETAIL = 'BD';

	public static function getInstance()
	{
		static $instance = null;

		if ($instance === null)
		{
			$instance = new static();
		}

		return $instance;
	}

	public function getCategoryPrefixTitle(int $categoryId): string
	{
		static $categoryTitles = null;

		if ($categoryTitles === null)
		{
			$categoryTitles = [
				static::REQUISITE => Loc::getMessage('CRM_DUP_VOLATILE_FIELD_CATEGORY_REQUISITE'),
				static::BANK_DETAIL => Loc::getMessage('CRM_DUP_VOLATILE_FIELD_CATEGORY_BANK_DETAIL'),
			];
		}

		return $categoryTitles[$categoryId] ?? '';
	}

	public function getCategoryByPath(string $fieldPathName): array
	{
		$result = [
			'categoryId' => static::UNDEFINED,
			'categoryPrefixTitle' => '',
			'params' => [],
		];

		$fieldInfo = FieldInfo::getInstance();

		[$path, $name] = array_values($fieldInfo->splitFieldPath($fieldPathName));

		$fieldPathLength = mb_strlen($path);
		if ($fieldPathLength <= 0)
		{
			if ($name === 'ADDRESS')
			{
				$result['categoryId'] = static::ADDRESS;
			}
			else
			{
				$result['categoryId'] = static::ENTITY;
			}
		}
		else
		{
			$subTypeCode1 = ($fieldPathLength >= 2) ? mb_substr($path, 0, 2) : '';
			if ($fieldPathLength === 2)
			{
				if ($subTypeCode1 === FieldCategory::PREFIX_MULTI)
				{
					$multiFieldType = $name;
					$multiFieldTypeMap = CCrmFieldMulti::GetEntityTypes();
					if (isset($multiFieldTypeMap[$multiFieldType]))
					{
						$result['categoryId'] = static::MULTI;
						$result['params']['multiFieldType'] = $multiFieldType;
					}
				}
				elseif ($subTypeCode1 === FieldCategory::PREFIX_REQUISITE)
				{
					if ($name === EntityRequisite::ADDRESS)
					{
						$result['categoryId'] = static::ADDRESS;
					}
				}
			}
			elseif (
				$fieldPathLength >= 5 && $path[2] === '.'
				&& $subTypeCode1 === FieldCategory::PREFIX_REQUISITE
			)
			{
				$countryCode = mb_substr($path, 3, 2);
				$countryId = (int)GetCountryIdByCode($countryCode);

				if ($fieldPathLength === 5)
				{
					$result['categoryId'] = static::REQUISITE;
					$result['params']['countryId'] = $countryId;
				}
				elseif ($countryId > 0 && $fieldPathLength === 8 && $path[5] === '.')
				{
					$subTypeCode2 = mb_substr($path, 6);
					if ($subTypeCode2 === FieldCategory::PREFIX_BANK_DETAIL)
					{
						$result['categoryId'] = static::BANK_DETAIL;
						$result['params']['countryId'] = $countryId;
					}
				}
			}
		}

		$result['categoryPrefixTitle'] = $this->getCategoryPrefixTitle($result['categoryId']);

		return $result;
	}
}

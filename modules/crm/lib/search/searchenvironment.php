<?php

namespace Bitrix\Crm\Search;

use CCrmUserType;

class SearchEnvironment
{
	private static array $supportedUserFieldTypeIds = [
		'address',
		'string',
		'integer',
		'double',
		'boolean',
		'date',
		'datetime',
		'enumeration',
		'employee',
		'file',
		'url',
		'crm',
		'crm_status',
		'iblock_element',
		'iblock_section'
	];

	public static function prepareToken(string $str): string
	{
		return str_rot13($str);
	}

	public static function prepareEntityFilter(int $entityTypeId, array $params): array
	{
		return SearchContentBuilderFactory::create($entityTypeId)->prepareEntityFilter($params);
	}

	public static function prepareSearchFilter(int $entityTypeId, &$filter, array $options = []): void
	{
		if (!is_array($filter))
		{
			return;
		}

		foreach ($filter as $key => &$value)
		{
			if (is_array($value))
			{
				self::prepareSearchFilter($entityTypeId, $value, $options);
			}
		}
		unset($value);

		if (
			isset($filter['SEARCH_CONTENT'])
			&& !is_array($filter['SEARCH_CONTENT'])
			&& $filter['SEARCH_CONTENT'] !== ''
		)
		{
			$searchFilter = self::prepareEntityFilter(
				$entityTypeId,
				[
					'SEARCH_CONTENT' => self::prepareSearchContent($filter['SEARCH_CONTENT'], $options)
				]
			);

			unset($filter['SEARCH_CONTENT']);
			$filter[] = $searchFilter;
			unset($searchFilter);
		}
	}

	public static function convertEntityFilterValues(int $entityTypeId, array &$fields): void
	{
		SearchContentBuilderFactory::create($entityTypeId)->convertEntityFilterValues($fields);
	}

	public static function prepareSearchContent($str, array $options = [])
	{
		if (
			!isset($options['ENABLE_PHONE_DETECTION'])
			|| $options['ENABLE_PHONE_DETECTION'] !== false
		)
		{
			$numCount = mb_strlen(preg_replace('/[^0-9]/', '', $str));
			if (
				$numCount >= 3 
				&& $numCount <= 15 
				&& preg_match('/^[0-9\(\)\+\-\#\;\,\*\s]+$/', $str) === 1
			)
			{
				$str = NormalizePhone($str, 3);
			}
		}

		return $str;
	}

	public static function getUserFields(int $entityId, string $userFieldEntityId): array
	{
		if (empty($userFieldEntityId))
		{
			return [];
		}

		global $USER_FIELD_MANAGER;

		$userTypeEntity = new CCrmUserType($USER_FIELD_MANAGER, $userFieldEntityId);
		$userTypeMap = array_fill_keys(self::$supportedUserFieldTypeIds, true);
		$userFields = $userTypeEntity->GetEntityFields($entityId);
		$results = [];
		foreach ($userFields as $userField)
		{
			if (isset($userTypeMap[$userField['USER_TYPE_ID']]))
			{
				$results[] = $userField;
			}
		}

		return $results;
	}
}

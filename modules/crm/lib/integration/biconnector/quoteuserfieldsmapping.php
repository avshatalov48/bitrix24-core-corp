<?php

namespace Bitrix\Crm\Integration\BiConnector;

use Bitrix\BIConnector\MemoryCache;
use Bitrix\BIConnector\PrettyPrinter;
use Bitrix\Main\Application;

final class QuoteUserFieldsMapping
{
	public static function getMapping(string $languageId): ?array
	{
		$userFieldManager = Application::getUserTypeManager();

		$userFields = $userFieldManager->getUserFields(\CCrmQuote::$sUFEntityID, 0, $languageId);
		if (!$userFields)
		{
			return null;
		}

		$result = [
			'TABLE_NAME' => 'b_uts_crm_quote',
			'TABLE_ALIAS' => 'QUF',
			'TABLE_DESCRIPTION' =>  Localization::getMessage('CRM_QUOTE_UF_TABLE', $languageId),
			'FIELDS' => [
				'QUOTE_ID' => [
					'IS_PRIMARY' => 'Y',
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'QUF.VALUE_ID',
					'FIELD_TYPE' => 'int',
					'FIELD_DESCRIPTION' => Localization::getMessage('CRM_QUOTE_UF_FIELD_QUOTE_ID', $languageId),
				],
				//b_crm_quote.DATE_CREATE DATETIME NULL,
				'DATE_CREATE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'Q.DATE_CREATE',
					'FIELD_TYPE' => 'datetime',
					'TABLE_ALIAS' => 'Q',
					'JOIN' => 'INNER JOIN b_crm_quote Q ON Q.ID = QUF.VALUE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_quote Q ON Q.ID = QUF.VALUE_ID',
					'FIELD_DESCRIPTION' => Localization::getMessage('CRM_QUOTE_UF_FIELD_DATE_CREATE', $languageId),
				],
				//b_crm_quote.CLOSEDATE DATETIME DEFAULT NULL,
				'CLOSEDATE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'Q.CLOSEDATE',
					'FIELD_TYPE' => 'datetime',
					'TABLE_ALIAS' => 'Q',
					'JOIN' => 'INNER JOIN b_crm_quote Q ON Q.ID = QUF.VALUE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_quote Q ON Q.ID = QUF.VALUE_ID',
					'FIELD_DESCRIPTION' => Localization::getMessage('CRM_QUOTE_UF_FIELD_CLOSEDATE', $languageId),
				],
			],
		];

		foreach ($userFields as $userField)
		{
			$dbType = '';
			if ($userField['USER_TYPE'] && is_callable([$userField['USER_TYPE']['CLASS_NAME'], 'getdbcolumntype']))
			{
				$dbType = call_user_func([$userField['USER_TYPE']['CLASS_NAME'], 'getdbcolumntype'], $userField);
			}

			$fieldMapping = [
				'FIELD_DESCRIPTION' => $userField['EDIT_FORM_LABEL'],
				'IS_METRIC' => 'N',
				'FIELD_NAME' => 'QUF.' . $userField['FIELD_NAME'],
				'FIELD_TYPE' => 'string',
			];

			if ($dbType === 'date' && $userField['MULTIPLE'] === 'N')
			{
				$fieldMapping['FIELD_TYPE'] = 'date';
			}
			elseif ($dbType === 'datetime' && $userField['MULTIPLE'] === 'N')
			{
				$fieldMapping['FIELD_TYPE'] = 'datetime';
			}
			else
			{
				$fieldMapping['CALLBACK'] = self::getClosure($userField, $dbType);
			}

			$result['FIELDS'][$userField['FIELD_NAME']] = $fieldMapping;
		}

		return $result;
	}

	public static function getClosure(mixed $userField, string $dbType): \Closure
	{
		return static function ($value, $dateFormats) use ($userField, $dbType) {
			if (in_array($dbType, ['date', 'datetime']))
			{
				$format = 'date_format_php';

				if ($dbType === 'datetime')
				{
					$format = 'datetime_format_php';
				}

				return PrettyPrinter::formatUserFieldAsDate($userField, $value, $dateFormats[$format]);
			}

			$cacheKey = serialize($value);
			$cachedResult = MemoryCache::get($userField['ID'], $cacheKey);
			if (isset($cachedResult))
			{
				return $cachedResult;
			}

			if ($userField['MULTIPLE'] === 'Y')
			{
				$result = Application::getUserTypeManager()->onAfterFetch(
					$userField,
					unserialize($value, ['allowed_classes' => PrettyPrinter::$allowedUnserializeClassesList])
				);
			}
			else
			{
				$result = [Application::getUserTypeManager()->onAfterFetch($userField, $value)];
			}

			$localUF = $userField;
			$localUF['VALUE'] = $result;

			$returnResult = Application::getUserTypeManager()->getPublicText($localUF);
			MemoryCache::set($userField['ID'], $cacheKey, $returnResult);

			return $returnResult;
		};
	}
}

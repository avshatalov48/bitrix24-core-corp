<?php

namespace Bitrix\Crm\Format;

use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;

abstract class Requisite
{
	final public static function formatOrganizationName(array $requisites): ?string
	{
		if (!empty($requisites[EntityRequisite::COMPANY_NAME]))
		{
			return (string)$requisites[EntityRequisite::COMPANY_NAME];
		}

		if (!empty($requisites[EntityRequisite::COMPANY_FULL_NAME]))
		{
			return (string)$requisites[EntityRequisite::COMPANY_FULL_NAME];
		}

		if (isset($requisites['PRESET_ID']) && $requisites['PRESET_ID'] > 0)
		{
			$preset = static::getPreset((int)$requisites['PRESET_ID']);
			if (
				is_array($preset)
				&& isset($preset['XML_ID'])
				&& (string)$preset['XML_ID'] === EntityRequisite::XML_ID_DEFAULT_PRESET_RU_INDIVIDUAL
			)
			{
				$firstName = $requisites[EntityRequisite::PERSON_FIRST_NAME] ?? null;
				$secondName = $requisites[EntityRequisite::PERSON_SECOND_NAME] ?? null;
				$lastName = $requisites[EntityRequisite::PERSON_LAST_NAME] ?? null;

				if (!empty($firstName) && !empty($secondName) && !empty($lastName))
				{
					$firstNameInitial = mb_substr((string)$firstName, 0, 1);
					$secondNameInitial = mb_substr((string)$secondName, 0, 1);

					return Loc::getMessage(
						'CRM_FORMAT_REQUISITE_ORG_NAME_INDIVIDUAL_RU',
						[
							'#FIRST_NAME_INITIAL#' => $firstNameInitial,
							'#SECOND_NAME_INITIAL#' => $secondNameInitial,
							'#LAST_NAME#' => $lastName,
						],
						'ru',
					);
				}
			}
		}

		return null;
	}

	public static function formatShortRequisiteString(array $requisites): ?string
	{
		$localization = Container::getInstance()->getLocalization();
		$presetId = (int)($requisites['PRESET_ID'] ?? 0);
		if ($presetId <= 0)
		{
			return null;
		}
		$preset = static::getPreset((int)$requisites['PRESET_ID']);
		if (!is_array($preset))
		{
			return null;
		}
		$defaultTitles = EntityRequisite::getSingleInstance()->getFieldsTitles();
		$presetFields = [];
		foreach ((array)($preset['SETTINGS']['FIELDS'] ?? []) as $presetField)
		{
			$presetFields[$presetField['FIELD_NAME']] = $presetField;
		}

		$significantFieldNames = static::getSignificantFieldNames();
		foreach ($significantFieldNames as $significantFieldName)
		{
			if (!empty($requisites[$significantFieldName]))
			{
				$title = $presetFields[$significantFieldName]['FIELD_TITLE'] ?? null;
				if (empty($title))
				{
					$title = $defaultTitles[$significantFieldName] ?? null;
				}
				if ($title)
				{
					return $localization->prepareFieldValueWithTitle($title, $requisites[$significantFieldName]);
				}
			}
		}
		foreach ($presetFields as $fieldName => $presetField)
		{
			if (!empty($requisites[$fieldName]))
			{
				$title = $presetFields[$fieldName]['FIELD_TITLE'] ?? $defaultTitles[$fieldName] ?? null;
				if ($title)
				{
					return $localization->prepareFieldValueWithTitle($title, $requisites[$fieldName]);
				}
			}
		}

		return null;
	}

	protected static function getSignificantFieldNames(): array
	{
		return [
			\Bitrix\Crm\EntityRequisite::INN,
			\Bitrix\Crm\EntityRequisite::IIN,
			\Bitrix\Crm\EntityRequisite::VAT_ID,
		];
	}

	/**
	 * Is extracted for testing purposes
	 *
	 * @param int $presetId
	 * @return array|null
	 */
	protected static function getPreset(int $presetId): ?array
	{
		return EntityPreset::getSingleInstance()->getById($presetId);
	}
}

<?php

namespace Bitrix\BIConnector\ExternalSource;

use Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceTable;
use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Result;

final class RestDatasetManager extends DatasetManager
{
	public static function updateFieldsByDatasetId(
		ExternalDataset $dataset,
		array $fieldsToAdd = [],
		array $fieldsToUpdate = [],
		array $fieldsToDelete = [],
	): Result
	{
		$result = new Result();
		$datasetId = $dataset->getId();
		$currentFields = self::getDatasetFieldsById($datasetId);

		$checkFieldsToAddResult = self::checkFieldsBeforeAdd(
			$fieldsToAdd,
			$currentFields->getExternalCodeList(),
			$currentFields->getNameList()
		);
		if (!$checkFieldsToAddResult->isSuccess())
		{
			$result->addErrors($checkFieldsToAddResult->getErrors());

			return $result;
		}

		$fieldsToUpdate = array_filter($fieldsToUpdate, static function ($field) use ($currentFields) {
			return isset($field['ID']) && $currentFields->getByPrimary($field['ID']) !== null;
		});

		$db = Application::getInstance()->getConnection();
		$db->startTransaction();

		foreach ($fieldsToUpdate as $fieldToUpdate)
		{
			/** @var Internal\ExternalDatasetField $currentField */
			$currentField = $currentFields->getByPrimary($fieldToUpdate['ID']);
			if (isset($fieldToUpdate['VISIBLE']) && $fieldToUpdate['VISIBLE'] !== $currentField->getVisible())
			{
				// update only VISIBLE field
				$currentField->setVisible((bool)$fieldToUpdate['VISIBLE']);
				$saveFieldResult = $currentField->save();
				if (!$saveFieldResult->isSuccess())
				{
					$result->addErrors($saveFieldResult->getErrors());
				}
			}
		}

		if ($fieldsToAdd)
		{
			$addFieldsResult = self::addFieldsToDataset($datasetId, $fieldsToAdd);
			if (!$addFieldsResult->isSuccess())
			{
				$result->addErrors($addFieldsResult->getErrors());
			}
		}

		if ($fieldsToDelete)
		{
			Internal\ExternalDatasetFieldTable::deleteByFilter(['=ID' => $fieldsToDelete]);
		}

		if ($result->isSuccess())
		{
			$db->commitTransaction();
		}
		else
		{
			$db->rollbackTransaction();
		}

		$event = new Event(
			'biconnector',
			self::EVENT_ON_AFTER_UPDATE_DATASET,
			[
				'dataset' => self::getById($datasetId),
			]
		);
		$event->send();

		foreach ($event->getResults() as $eventResult)
		{
			if ($eventResult->getType() === EventResult::ERROR)
			{
				$error = $eventResult->getParameters();
				$result->addError(
					$error instanceof Error
						? $error
						: new Error('Error updating dataset.', 'DATASET_UPDATE_ERROR')
				);
			}
		}

		return $result;
	}

	protected static function checkAndPrepareBeforeAdd(
		array $dataset,
		array $fields,
		array $settings,
		int $sourceId = null
	): Result
	{
		$result = parent::checkAndPrepareBeforeAdd($dataset, $fields, $settings, $sourceId);

		if (!$result->isSuccess())
		{
			return $result;
		}

		if (!$sourceId)
		{
			$result->addError(new Error(
				'The "sourceId" parameter is required.',
				'VALIDATION_SOURCE_ID_REQUIRED'
			));

			return $result;
		}

		$source = ExternalSourceTable::getById($sourceId)->fetchObject();
		if (!$source || Type::tryFrom($source->getType()) !== Type::Rest)
		{
			$result->addError(new Error('Source was not found.', 'SOURCE_NOT_FOUND'));

			return $result;
		}

		$checkFieldsResult = self::checkFields($fields);
		if (!$checkFieldsResult->isSuccess())
		{
			$result->addErrors($checkFieldsResult->getErrors());
		}

		if (mb_strlen($dataset['NAME']) > 230)
		{
			$result->addError(new Error('Dataset name must not exceed 230 characters.', 'VALIDATION_DATASET_NAME_TOO_LONG'));
		}

		return $result;
	}

	private static function checkFields(array $fields): Result
	{
		$result = new Result();

		if ($fields)
		{
			$fieldCodes = array_column($fields, 'EXTERNAL_CODE');
			$duplicatesCodes = array_filter(array_count_values($fieldCodes), static function($count) {
				return $count > 1;
			});
			if ($duplicatesCodes)
			{
				$result->addError(new Error(
					'Duplicate values found in the "code" parameter: '
					. implode(', ', array_keys($duplicatesCodes)),
					'VALIDATION_DUPLICATE_FIELD_CODE'
				));
			}

			$fieldNames = array_column($fields, 'NAME');
			$duplicatesNames = array_filter(array_count_values($fieldNames), static function($count) {
				return $count > 1;
			});
			if ($duplicatesNames)
			{
				$result->addError(new Error(
					'Duplicate values found in the "name" parameter: '
					. implode(', ', array_keys($duplicatesNames)),
					'VALIDATION_DUPLICATE_FIELD_NAME'
				));
			}

			foreach ($fields as $field)
			{
				if (
					!is_array($field)
					|| !array_key_exists('NAME', $field)
					|| !array_key_exists('TYPE', $field)
					|| !array_key_exists('EXTERNAL_CODE', $field)
				)
				{
					$result->addError(new Error(
						'Field must include the required parameters: "name", "externalCode" and "type".',
						'VALIDATION_FIELD_MISSING_REQUIRED_PARAMETERS'
					));

					return $result;
				}

				if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $field['NAME']))
				{
					$result->addError(new Error(
						'Field "name" has to start with an uppercase Latin character. Possible entry includes uppercase Latin characters (A-Z), numbers (0-9) and underscores.',
						'VALIDATION_FIELD_NAME_INVALID_FORMAT'
					));
				}

				if (mb_strlen($field['NAME']) > 32)
				{
					$result->addError(new Error(
						'Field "name" must not exceed 32 characters.',
						'VALIDATION_FIELD_NAME_TOO_LONG'
					));
				}

				if (FieldType::tryFrom($field['TYPE']) === null)
				{
					$result->addError(new Error('Invalid field type.' , 'VALIDATION_FIELD_INVALID_TYPE'));
				}
			}
		}

		return $result;
	}

	private static function checkFieldsBeforeAdd(
		array $fieldsToAdd,
		array $currentCodes,
		array $currentNames,
	): Result
	{
		if (empty($fieldsToAdd))
		{
			return new Result();
		}

		$result = self::checkFields($fieldsToAdd);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$duplicatesCodes = array_intersect($currentCodes, array_column($fieldsToAdd, 'EXTERNAL_CODE'));
		$duplicatesCodes = array_unique($duplicatesCodes);
		if ($duplicatesCodes)
		{
			$result->addError(new Error(
				'The following "externalCode" values already exist in the current fields: '
				. implode(', ', $duplicatesCodes),
				'VALIDATION_DUPLICATE_EXIST_CODE'
			));
		}

		$duplicatesNames = array_intersect($currentNames, array_column($fieldsToAdd, 'NAME'));
		$duplicatesNames = array_unique($duplicatesNames);
		if ($duplicatesNames)
		{
			$result->addError(new Error(
				'The following "name" values already exist in the current fields: '
				. implode(', ', $duplicatesNames),
				'VALIDATION_DUPLICATE_EXIST_NAME'
			));
		}

		return $result;
	}
}

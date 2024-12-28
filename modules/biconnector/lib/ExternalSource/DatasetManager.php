<?php

namespace Bitrix\BIConnector\ExternalSource;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\CurrentUser;

final class DatasetManager
{
	public const EVENT_ON_AFTER_ADD_DATASET = 'onAfterAddDataset';
	public const EVENT_ON_AFTER_DELETE_DATASET = 'onAfterDeleteDataset';

	/**
	 * Adds new dataset with field and settings
	 *
	 * @param array $dataset
	 * @param array $fields
	 * @param array $settings
	 * @param int|null $sourceId
	 *
	 * @return Main\Result
	 */
	public static function add(array $dataset, array $fields, array $settings, int $sourceId = null): Main\Result
	{
		$result = new Main\Result();

		$checkResult = self::checkAndPrepareBeforeAdd($dataset, $fields, $settings);
		if (!$checkResult->isSuccess())
		{
			$result->addErrors($checkResult->getErrors());

			return $result;
		}

		$checkResultData = $checkResult->getData();

		$dataset = $checkResultData['dataset'];
		$fields = $checkResultData['fields'];
		$settings = $checkResultData['settings'];

		$connection = Main\Application::getInstance()->getConnection();
		$connection->startTransaction();

		$id = null;

		$datasetAddResult = Internal\ExternalDatasetTable::add($dataset);
		if ($datasetAddResult->isSuccess())
		{
			$id = $datasetAddResult->getId();

			$addFieldsResult = self::addFieldsToDataset($id, $fields);
			if (!$addFieldsResult->isSuccess())
			{
				$result->addErrors($addFieldsResult->getErrors());
			}

			$addSettingsResult = self::addSettingsToDataset($id, $settings);
			if (!$addSettingsResult->isSuccess())
			{
				$result->addErrors($addSettingsResult->getErrors());
			}

			if ($sourceId)
			{
				$relationAddResult = Internal\ExternalSourceDatasetRelationTable::addRelation($sourceId, $id);
				{
					$result->addErrors($relationAddResult->getErrors());
				}
			}
		}
		else
		{
			$result->addErrors($datasetAddResult->getErrors());
		}

		if ($result->isSuccess())
		{
			$connection->commitTransaction();

			$event = new Main\Event(
				'biconnector',
				self::EVENT_ON_AFTER_ADD_DATASET,
				[
					'dataset' => self::getById($id),
				]
			);
			$event->send();

			foreach ($event->getResults() as $eventResult)
			{
				if ($eventResult->getType() === Main\EventResult::ERROR)
				{
					$error = $eventResult->getParameters();
					$result->addError(
						$error instanceof Main\Error
							? $error
							: new Main\Error(Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_DATASET_MANAGER_ADD_ERROR'))
					);
				}
			}

			if ($result->isSuccess())
			{
				$result->setData([
					'id' => $id,
					'dataset' => $datasetAddResult->getData(),
				]);
			}
			else
			{
				self::delete($id);
			}
		}
		else
		{
			$connection->rollbackTransaction();
		}

		return $result;
	}

	private static function checkAndPrepareBeforeAdd(array $dataset, array $fields, array $settings): Main\Result
	{
		$result = new Main\Result();

		$checkBeforeResult = self::checkBefore($dataset, $fields, $settings);
		if (!$checkBeforeResult->isSuccess())
		{
			$result->addErrors($checkBeforeResult->getErrors());
		}

		if (empty($dataset))
		{
			$result->addError(new Main\Error('$dataset is empty', 'DATASET_EMPTY'));
		}
		else
		{
			if (in_array($dataset['NAME'], SupersetServiceIntegration::getTableList(), true))
			{
				$result->addError(
					new Main\Error(
						Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_DATASET_MANAGER_DATASET_ALREADY_EXIST_ERROR')
					)
				);
			}

			$dataset['DATE_CREATE'] = new Main\Type\DateTime();

			if (empty($dataset['CREATED_BY_ID']) && CurrentUser::get()->getId())
			{
				$dataset['CREATED_BY_ID'] = CurrentUser::get()->getId();
			}
		}

		if (empty($fields))
		{
			$result->addError(new Main\Error('$fields is empty', 'FIELDS_EMPTY'));
		}

		if (empty($settings))
		{
			$result->addError(new Main\Error('$settings is empty', 'SETTINGS_EMPTY'));
		}

		$result->setData([
			'dataset' => $dataset,
			'fields' => $fields,
			'settings' => $settings,
		]);

		return $result;
	}

	/**
	 * Updates dataset with field and settings
	 *
	 * @param int $id
	 * @param array $dataset
	 * @param array $fields
	 * @param array $settings
	 * @return Main\Result
	 */
	public static function update(int $id, array $dataset = [], array $fields = [], array $settings = []): Main\Result
	{
		$result = new Main\Result();

		$checkResult = self::checkAndPrepareBeforeUpdate($dataset, $fields, $settings);
		if (!$checkResult->isSuccess())
		{
			$result->addErrors($checkResult->getErrors());

			return $result;
		}

		$checkResultData = $checkResult->getData();

		$dataset = $checkResultData['dataset'];
		$fields = $checkResultData['fields'];
		$settings = $checkResultData['settings'];

		$connection = Main\Application::getInstance()->getConnection();
		$connection->startTransaction();

		if ($dataset)
		{
			$datasetUpdateResult = Internal\ExternalDatasetTable::update($id, $dataset);
			if (!$datasetUpdateResult->isSuccess())
			{
				$result->addErrors($datasetUpdateResult->getErrors());
			}
		}

		if ($fields)
		{
			$currentFields = self::getDatasetFieldsById($id);
			foreach ($fields as $field)
			{
				$fieldId = $field['ID'] ? (int)$field['ID'] : null;
				if ($fieldId)
				{
					$currentField = $currentFields->getByPrimary($fieldId);
					if (isset($field['VISIBLE']) && $field['VISIBLE'] !== $currentField->getVisible())
					{
						// update only VISIBLE field
						$currentField->setVisible($field['VISIBLE']);
						$saveFieldResult = $currentField->save();
						if (!$saveFieldResult->isSuccess())
						{
							$result->addErrors($saveFieldResult->getErrors());
						}
					}
				}
			}
		}

		if ($settings)
		{
			$deleteDatasetSettingsResult = Internal\ExternalDatasetFieldFormatTable::deleteByDatasetId($id);
			if (!$deleteDatasetSettingsResult->isSuccess())
			{
				$result->addErrors($deleteDatasetSettingsResult->getErrors());
			}

			$addSettingsResult = self::addSettingsToDataset($id, $settings);
			if (!$addSettingsResult->isSuccess())
			{
				$result->addErrors($addSettingsResult->getErrors());
			}
		}

		if ($result->isSuccess())
		{
			$connection->commitTransaction();
		}
		else
		{
			$connection->rollbackTransaction();
		}

		return $result;
	}

	private static function checkAndPrepareBeforeUpdate(array $dataset, array $fields, array $settings): Main\Result
	{
		$result = new Main\Result();

		$checkBeforeResult = self::checkBefore($dataset, $fields, $settings);
		if (!$checkBeforeResult->isSuccess())
		{
			$result->addErrors($checkBeforeResult->getErrors());
		}

		if (!empty($dataset))
		{
			$dataset['DATE_UPDATE'] = new Main\Type\DateTime();

			if (empty($dataset['UPDATED_BY_ID']) && CurrentUser::get()->getId())
			{
				$dataset['UPDATED_BY_ID'] = CurrentUser::get()->getId();
			}

			unset($dataset['NAME']);
		}

		$result->setData([
			'dataset' => $dataset,
			'fields' => $fields,
			'settings' => $settings,
		]);

		return $result;
	}

	private static function checkBefore(array $dataset, array $fields, array $settings): Main\Result
	{
		$result = new Main\Result();

		if ($fields)
		{
			$fieldNames = array_column($fields, 'NAME');
			$duplicates = array_filter(array_count_values($fieldNames), static function($count) {
				return $count > 1;
			});
			if ($duplicates)
			{
				$result->addError(new Main\Error('Duplicate column names: ' . implode(', ', array_keys($duplicates))));
			}
		}

		return $result;
	}

	private static function addFieldsToDataset(int $id, array $fields): Main\Result
	{
		$result = new Main\Result();

		foreach ($fields as $field)
		{
			$field['DATASET_ID'] = $id;
			$datasetFieldsAddResult = Internal\ExternalDatasetFieldTable::add($field);
			if (!$datasetFieldsAddResult->isSuccess())
			{
				$result->addErrors($datasetFieldsAddResult->getErrors());
			}
		}

		return $result;
	}

	private static function addSettingsToDataset(int $id, array $settings): Main\Result
	{
		$result = new Main\Result();

		foreach ($settings as $setting)
		{
			$setting['DATASET_ID'] = $id;
			$datasetSettingsAddResult = Internal\ExternalDatasetFieldFormatTable::add($setting);
			if (!$datasetSettingsAddResult->isSuccess())
			{
				$result->addErrors($datasetSettingsAddResult->getErrors());
			}
		}

		return $result;
	}

	/**
	 * Deletes dataset with fields and settings
	 *
	 * @param int $id
	 * @return Main\Result
	 */
	public static function delete(int $id): Main\Result
	{
		$result = new Main\Result();

		$dataset = self::getById($id);
		if (!$dataset)
		{
			return $result;
		}

		$connection = Main\Application::getInstance()->getConnection();
		$connection->startTransaction();

		$datasetDeleteResult = Internal\ExternalDatasetTable::delete($id);
		if ($datasetDeleteResult->isSuccess())
		{
			$event = new Main\Event(
				'biconnector',
				self::EVENT_ON_AFTER_DELETE_DATASET,
				[
					'dataset' => $dataset,
				]
			);
			$event->send();

			foreach ($event->getResults() as $eventResult)
			{
				if ($eventResult->getType() === Main\EventResult::ERROR)
				{
					$error = $eventResult->getParameters();
					$result->addError(
						$error instanceof Main\Error
							? $error
							: new Main\Error(Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_DATASET_MANAGER_DELETE_ERROR'))
					);
				}
			}
		}
		else
		{
			$result->addErrors($datasetDeleteResult->getErrors());
		}

		if ($result->isSuccess())
		{
			$connection->commitTransaction();
		}
		else
		{
			$connection->rollbackTransaction();
		}

		return $result;
	}

	/**
	 * Gets dataset data
	 *
	 * @param int $id
	 * @return Internal\ExternalDataset|null
	 */
	public static function getById(int $id): ?Internal\ExternalDataset
	{
		return Internal\ExternalDatasetTable::getById($id)?->fetchObject();
	}

	/**
	 * Gets list of datasets
	 *
	 * @param array $filter
	 * @return Internal\ExternalDatasetCollection
	 */
	public static function getList(array $filter = []): Internal\ExternalDatasetCollection
	{
		$datasetResult = Internal\ExternalDatasetTable::getList([
			'select' => ['*'],
			'filter' => $filter,
		])->fetchCollection();

		return $datasetResult;
	}

	/**
	 * Gets fields by dataset id
	 *
	 * @param int $id
	 * @return Internal\ExternalDatasetFieldCollection
	 */
	public static function getDatasetFieldsById(int $id): Internal\ExternalDatasetFieldCollection
	{
		$datasetFieldsResult = Internal\ExternalDatasetFieldTable::getList([
			'select' => ['*'],
			'filter' => [
				'=DATASET_ID' => $id
			]
		])->fetchCollection();

		return $datasetFieldsResult;
	}

	/**
	 * Gets settings by dataset id
	 *
	 * @param int $id
	 * @return Internal\ExternalDatasetFieldFormatCollection
	 */
	public static function getDatasetSettingsById(int $id): Internal\ExternalDatasetFieldFormatCollection
	{
		$datasetSettingsResult = Internal\ExternalDatasetFieldFormatTable::getList([
			'select' => ['*'],
			'filter' => [
				'=DATASET_ID' => $id
			]
		])->fetchCollection();

		return $datasetSettingsResult;
	}
}

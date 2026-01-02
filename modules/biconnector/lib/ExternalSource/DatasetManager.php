<?php

namespace Bitrix\BIConnector\ExternalSource;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\Response\Converter;

class DatasetManager
{
	public const EVENT_ON_BEFORE_ADD_DATASET = 'onBeforeAddDataset';
	public const EVENT_ON_AFTER_ADD_DATASET = 'onAfterAddDataset';
	public const EVENT_ON_BEFORE_UPDATE_DATASET = 'onBeforeUpdateDataset';
	public const EVENT_ON_AFTER_UPDATE_DATASET = 'onAfterUpdateDataset';
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
	public static function add(array $dataset, array $fields, array $settings = [], int $sourceId = null): Main\Result
	{
		$result = new Main\Result();

		$event = new Main\Event(
			'biconnector',
			self::EVENT_ON_BEFORE_ADD_DATASET,
			[
				'dataset' => $dataset,
				'fields' => $fields,
				'settings' => $settings,
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

				return $result;
			}
		}

		$checkResult = static::checkAndPrepareBeforeAdd($dataset, $fields, $settings, $sourceId);
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

			if ($settings)
			{
				$addSettingsResult = self::addSettingsToDataset($id, $settings);
				if (!$addSettingsResult->isSuccess())
				{
					$result->addErrors($addSettingsResult->getErrors());
				}
			}

			if ($sourceId)
			{
				$relationAddResult = Internal\ExternalSourceDatasetRelationTable::addRelation($sourceId, $id);
				if (!$relationAddResult->isSuccess())
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

	protected static function checkAndPrepareBeforeAdd(
		array $dataset,
		array $fields,
		array $settings,
		int $sourceId = null
	): Main\Result
	{
		$result = new Main\Result();

		$checkBeforeResult = self::checkBefore($dataset, $fields, $settings);
		if (!$checkBeforeResult->isSuccess())
		{
			$result->addErrors($checkBeforeResult->getErrors());

			return $result;
		}

		if (empty($dataset))
		{
			$result->addError(new Main\Error('$dataset is empty', 'DATASET_EMPTY'));
		}
		else
		{
			$datasetName = $dataset['NAME'];
			if (in_array($datasetName, SupersetServiceIntegration::getTableList(), true))
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

		if (empty($settings) && is_null($sourceId))
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

		$event = new Main\Event(
			'biconnector',
			self::EVENT_ON_BEFORE_UPDATE_DATASET,
			[
				'id' => $id,
				'dataset' => $dataset,
				'fields' => $fields,
				'settings' => $settings,
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
						: new Main\Error(Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_DATASET_MANAGER_UPDATE_ERROR'))
				);

				return $result;
			}
		}

		$checkResult = static::checkAndPrepareBeforeUpdate($id, $dataset, $fields, $settings);
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

			if (isset($fields[0]['id'], $fields[0]['visible']))
			{
				$converter = new Converter(Converter::KEYS | Converter::TO_UPPER | Converter::RECURSIVE);
				$fields = $converter->process($fields);
			}

			$fieldsToUpdate = array_filter($fields, static function ($field) use ($currentFields) {
				return isset($field['ID']) && $currentFields->getByPrimary($field['ID']) !== null;
			});

			foreach ($fieldsToUpdate as $fieldToUpdate)
			{
				$currentField = $currentFields->getByPrimary($fieldToUpdate['ID']);
				if (isset($fieldToUpdate['VISIBLE']) && $fieldToUpdate['VISIBLE'] !== $currentField->getVisible())
				{
					$currentField->setVisible($fieldToUpdate['VISIBLE']);
				}

				if (isset($fieldToUpdate['NAME']) && $fieldToUpdate['NAME'] !== $currentField->getName())
				{
					$currentField->setName($fieldToUpdate['NAME']);
				}

				if (
					$currentField->isVisibleChanged()
					|| $currentField->isNameChanged()
				)
				{
					$saveFieldResult = $currentField->save();
					if (!$saveFieldResult->isSuccess())
					{
						$result->addErrors($saveFieldResult->getErrors());
					}
				}
			}

			$fieldsToAdd = array_filter($fields, static function ($field) {
				return !isset($field['ID']) || (int)$field['ID'] === 0;
			});
			if ($fieldsToAdd)
			{
				$addFieldsResult = self::addFieldsToDataset($id, $fieldsToAdd);
				if (!$addFieldsResult->isSuccess())
				{
					$result->addErrors($addFieldsResult->getErrors());
				}
			}

			$fieldsToDelete = array_diff(
				$currentFields->getIdList(),
				array_map('intval', array_column($fields, 'ID'))
			);
			if ($fieldsToDelete)
			{
				Internal\ExternalDatasetFieldTable::deleteByFilter(['=ID' => $fieldsToDelete]);
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

			return $result;
		}

		$event = new Main\Event(
			'biconnector',
			self::EVENT_ON_AFTER_UPDATE_DATASET,
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
						: new Main\Error(Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_DATASET_MANAGER_UPDATE_ERROR'))
				);
			}
		}

		return $result;
	}

	protected static function checkAndPrepareBeforeUpdate(int $id, array $dataset, array $fields, array $settings): Main\Result
	{
		$result = new Main\Result();

		$checkBeforeResult = self::checkBefore($dataset, $fields, $settings);
		if (!$checkBeforeResult->isSuccess())
		{
			$result->addErrors($checkBeforeResult->getErrors());

			return $result;
		}

		$currentDataset = self::getById($id);
		if (!$currentDataset)
		{
			$result->addError(
				new Main\Error(
					Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_DATASET_MANAGER_DATASET_NOT_FOUND')
				)
			);

			return $result;
		}

		if ($dataset)
		{
			$dataset['DATE_UPDATE'] = new Main\Type\DateTime();

			if (empty($dataset['UPDATED_BY_ID']) && CurrentUser::get()->getId())
			{
				$dataset['UPDATED_BY_ID'] = CurrentUser::get()->getId();
			}

			unset($dataset['NAME'], $dataset['TYPE']);
		}

		$result->setData([
			'dataset' => $dataset,
			'fields' => $fields,
			'settings' => $settings,
		]);

		return $result;
	}

	protected static function checkBefore(array $dataset, array $fields, array $settings): Main\Result
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

	protected static function addFieldsToDataset(int $id, array $fields): Main\Result
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

<?php

namespace Bitrix\BIConnector\DataSource;

use Bitrix\BIConnector\DataSourceConnector\ApacheSupersetFieldDto;
use Bitrix\BIConnector\DataSourceConnector\Connector\Base;
use Bitrix\BIConnector\DataSourceConnector\FieldCollection;
use Bitrix\BIConnector\DataSourceConnector\FieldDto;
use Bitrix\Main\Event;

abstract class BIBuilderDataset extends Dataset
{
	/**
	 * Event handler for OnBIBuilderDataSources event.
	 * Adds a key from `getResultTableName` to the second event parameter.
	 * Fills it with data to retrieve information from table.
	 *
	 * @param Event $event Event data.
	 *
	 * @return void
	 */
	public static function onBIBuilderDataSources(Event $event)
	{
		$params = $event->getParameters();
		$manager = $params[0];
		$languageId = $params[2];
		$connection = $manager->getDatabaseConnection();
		if (!$connection)
		{
			return;
		}

		$dataset = new static($connection, $languageId);
		if (!$dataset->onBeforeEvent()->isSuccess())
		{
			return;
		}

		$result = &$params[1];
		$tableName = $dataset->getResultTableName();
		/** Hack for loading localization messages for child dataset */
		$dataset->getTableDescription();

		$fields = new FieldCollection();
		foreach ($dataset->getDatasetFields() as $field)
		{
			$fields->add($dataset->prepareFieldDto($field));
		}

		$result[$tableName] = $dataset->getConnector($tableName, $fields, $dataset->getResult());
	}

	protected function prepareFieldDto(DatasetField $field): FieldDto
	{
		$fields = $field->getFormatted();

		return new ApacheSupersetFieldDto(
			$field->getCode(),
			$fields['FIELD_DESCRIPTION'] ?? '',
			$fields['FIELD_DESCRIPTION_FULL'] ?? '',
			$fields['FIELD_TYPE'] ?? 'string',
			($fieldInfo['IS_METRIC'] ?? 'N') === 'Y',
			($fieldInfo['IS_SYSTEM'] ?? 'Y') === 'Y',
			($fieldInfo['IS_PRIMARY'] ?? 'N') === 'Y',
			$fieldInfo['AGGREGATION_TYPE'] ?? null,
			$fieldInfo['GROUP_KEY'] ?? null,
			$fieldInfo['GROUP_CONCAT'] ?? null,
			$fieldInfo['GROUP_COUNT'] ?? null
		);
	}

	/**
	 * @param string $name
	 * @param FieldCollection $fields
	 * @param array $datasetInfo
	 *
	 * @return Base
	 */
	abstract protected function getConnector(string $name, FieldCollection $fields,	array $datasetInfo): Base;
}

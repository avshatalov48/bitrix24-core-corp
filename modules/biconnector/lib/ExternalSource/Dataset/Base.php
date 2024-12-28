<?php

namespace Bitrix\BIConnector\ExternalSource\Dataset;

use Bitrix\Main;
use Bitrix\BIConnector;

abstract class Base extends BIConnector\DataSource\BIBuilderDataset
{
	protected ?BIConnector\ExternalSource\Internal\ExternalDataset $dataset;

	/**
	 * @return Main\Result
	 */
	protected function onBeforeEvent(): Main\Result
	{
		$result = parent::onBeforeEvent();

		if (!BIConnector\Configuration\Feature::isExternalEntitiesEnabled())
		{
			$result->addError(new Main\Error('Feature is not enabled for this dataset'));
		}

		return $result;
	}

	public static function createDataset(
		BIConnector\ExternalSource\Internal\ExternalDataset $dataset,
		Main\DB\Connection $dataConnection,
		string $languageId = null
	): self
	{
		return (new static($dataConnection, $languageId))->setDataset($dataset);
	}

	private function setDataset(BIConnector\ExternalSource\Internal\ExternalDataset $dataset): self
	{
		$this->dataset = $dataset;

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	abstract protected function getResultTableName(): string;

	/**
	 * @inheritDoc
	 */
	abstract public function getSqlTableAlias(): string;

	/**
	 * @inheritDoc
	 */
	abstract protected function getConnectionTableName(): string;

	protected function getFields(): array
	{
		$result = [];

		$fields = BIConnector\ExternalSource\DatasetManager::getDatasetFieldsById($this->dataset->getId());
		foreach ($fields as $field)
		{
			if (!$field->getVisible())
			{
				continue;
			}

			$result[] = $this->getField($field);
		}

		return $result;
	}

	private function getField(BIConnector\ExternalSource\Internal\ExternalDatasetField $datasetField): BIConnector\DataSource\DatasetField
	{
		$type = $datasetField->getEnumType();
		$name = $datasetField->getName();

		$filed = match ($type) {
			Biconnector\ExternalSource\FieldType::Int => new BIConnector\DataSource\Field\IntegerField($name),
			Biconnector\ExternalSource\FieldType::String => new BIConnector\DataSource\Field\StringField($name),
			Biconnector\ExternalSource\FieldType::Double, Biconnector\ExternalSource\FieldType::Money => new BIConnector\DataSource\Field\DoubleField($name),
			Biconnector\ExternalSource\FieldType::Date => new BIConnector\DataSource\Field\DateField($name),
			Biconnector\ExternalSource\FieldType::DateTime => new BIConnector\DataSource\Field\DateTimeField($name)
		};

		$filed->setDescription($datasetField->getName());
		$filed->setDescriptionFull($datasetField->getName());

		return $filed;
	}

	protected function getTableDescription(): string
	{
		return $this->dataset->getDescription() ?: $this->dataset->getName();
	}

	protected function getConnector(string $name, BIConnector\DataSourceConnector\FieldCollection $fields, array $datasetInfo): BIConnector\DataSourceConnector\Connector\Base
	{
		return Connector\Factory::getConnector($this->dataset->getEnumType(), $name, $fields, $datasetInfo);
	}

	public static function onBIBuilderExternalDataSources(Main\Event $event)
	{
		$params = $event->getParameters();
		$manager = $params[0];
		$languageId = $params[2];
		$connection = $manager->getDatabaseConnection();
		if (!$connection)
		{
			return;
		}

		$result = &$params[1];

		$externalDatasets = BIConnector\ExternalSource\DatasetManager::getList();
		foreach ($externalDatasets as $externalDataset)
		{
			$dataset = Factory::getDataset($externalDataset, $connection, $languageId);
			if (!$dataset->onBeforeEvent()->isSuccess())
			{
				continue;
			}

			$fields = new BIConnector\DataSourceConnector\FieldCollection();
			foreach ($dataset->getDatasetFields() as $field)
			{
				$fields->add($dataset->prepareFieldDto($field));
			}

			$tableName = $dataset->getResultTableName();
			$result[$tableName] = $dataset->getConnector($tableName, $fields, $dataset->getResult());
		}
	}
}

<?php

namespace Bitrix\BIConnector\Controller\ExternalSource;

use Bitrix\Main;
use Bitrix\Main\SystemException;
use Bitrix\BIConnector\ExternalSource;

final class FileImporter
{
	private int $datasetId;
	private array $settings;

	private ExternalSource\Type $type;
	private string $name;

	public function __construct(int $datasetId, array $settings = null)
	{
		$this->datasetId = $datasetId;
		$this->settings = $settings;

		$this->initDataset();
	}

	/**
	 * Imports data from file to table
	 *
	 * @return Main\Result
	 */
	public function import(): Main\Result
	{
		$result = new Main\Result();

		$settings = $this->createImporterSettings();
		$importer = ExternalSource\Importer\Factory::getImporter($this->type, $settings);
		$importResult = $importer->import();
		if (!$importResult->isSuccess())
		{
			$result->addErrors($importResult->getErrors());
		}

		return $result;
	}

	/**
	 * Re-Imports data from file to table
	 *
	 * @return Main\Result
	 */
	public function reImport(): Main\Result
	{
		$result = new Main\Result();

		$settings = $this->createImporterSettings();
		$importer = ExternalSource\Importer\Factory::getImporter($this->type, $settings);
		$importResult = $importer->reImport();
		if (!$importResult->isSuccess())
		{
			$result->addErrors($importResult->getErrors());
		}

		return $result;
	}

	private function createImporterSettings(): ExternalSource\Importer\Settings
	{
		$datasetSettings = $this->getDatasetSettings();
		$fieldCollection = $this->initFields($datasetSettings);

		return new ExternalSource\Importer\Settings(
			tableName: $this->getTableName(),
			reader: $this->getReader(),
			fieldCollection: $fieldCollection
		);
	}

	private function initFields(ExternalSource\Internal\ExternalDatasetFieldFormatCollection $datasetSettings): ?ExternalSource\Importer\FieldCollection
	{
		$datasetFields = ExternalSource\DatasetManager::getDatasetFieldsById($this->datasetId);
		if ($datasetFields->isEmpty())
		{
			return null;
		}

		$fieldCollection = new ExternalSource\Importer\FieldCollection();

		foreach ($datasetFields as $datasetField)
		{
			$externalCode = $datasetField->getExternalCode() ?: $datasetField->getName();
			$name = $datasetField->getName();
			$type = $datasetField->getEnumType();
			$format = $datasetSettings->getFormatByType($type);

			$field = new ExternalSource\Importer\Field($externalCode, $name, $type, $format);

			$fieldCollection->add($field);
		}

		return $fieldCollection;
	}

	private function initDataset(): void
	{
		$dataset = ExternalSource\DatasetManager::getById($this->datasetId);
		if (!$dataset)
		{
			throw new SystemException("Dataset with id '{$this->datasetId}' not found");
		}

		$this->type = $dataset->getEnumType();
		$this->name = $dataset->getName();
	}

	private function getTableName(): string
	{
		return $this->name;
	}

	private function getDatasetSettings(): ExternalSource\Internal\ExternalDatasetFieldFormatCollection
	{
		$settings = ExternalSource\DatasetManager::getDatasetSettingsById($this->datasetId);

		return $settings;
	}

	private function getReader(): ExternalSource\FileReader\Base
	{
		return ExternalSource\FileReader\Factory::getReader($this->type, $this->settings);
	}
}

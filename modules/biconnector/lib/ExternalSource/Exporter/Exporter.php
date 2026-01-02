<?php

namespace Bitrix\BIConnector\ExternalSource\Exporter;

use Bitrix\BIConnector\ExternalSource\DatasetManager;
use Bitrix\Main\Result;

class Exporter
{
	protected const EXPORT_CHUNK_SIZE = 50000;

	protected Settings $settings;
	protected Formatter $formatter;

	public function __construct(Settings $settings)
	{
		$this->settings = $settings;

		$dataset = $this->settings->dataset;

		$datasetFields = DatasetManager::getDatasetFieldsById($dataset->getId());
		$datasetFieldFormats = DatasetManager::getDatasetSettingsById($dataset->getId());
		$this->formatter = new Formatter($datasetFields, $datasetFieldFormats);
	}

	public function export(): Result
	{
		$result = new Result();

		$dataset = $this->settings->dataset;

		$datasetFields = DatasetManager::getDatasetFieldsById($dataset->getId());

		$writer = $this->settings->writer;
		$writer->open();
		$headers = [];
		foreach ($datasetFields as $field)
		{
			$headers[] = $field->getName();
		}
		$writer->writeLine($headers);

		$offset = 0;
		$totalSize = $this->settings->dataProvider->getTotalSize();

		do
		{
			$rows = $this->settings->dataProvider->fetchChunk(static::EXPORT_CHUNK_SIZE, $offset);
			$writer->writeLines($this->rowGenerator($rows));

			unset($rows);

			$offset += static::EXPORT_CHUNK_SIZE;
		}
		while ($offset < $totalSize);

		$writer->close();

		$result->setData([
			'file' => $writer->getFile(),
		]);

		return $result;
	}

	protected function rowGenerator(iterable $rows): \Generator
	{
		foreach ($rows as $row)
		{
			yield $this->formatter->formatRow($row);
		}
	}
}

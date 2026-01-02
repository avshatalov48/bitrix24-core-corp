<?php

namespace Bitrix\BIConnector\ExternalSource\Viewer\Provider;

use Bitrix\BIConnector\ExternalSource\DatasetManager;
use Bitrix\Biconnector\ExternalSource\FieldType;
use Bitrix\BIConnector\ExternalSource\Source;
use Bitrix\BIConnector\ExternalSource;

final class Source1C implements Provider
{
	private const N_FIRST = 20;
	private int $sourceId;
	private ?array $settings = null;

	public function getData(): ProviderDataDto
	{
		/* @var ExternalSource\Source\Source1C $source */
		$source = Source\Factory::getSource(ExternalSource\Type::Source1C, $this->sourceId);
		$datasetId = (int)($this->settings['dataset']['ID'] ?? null);
		if ($datasetId)
		{
			$description = array_map(
				fn($item) => ['NAME' => $item->getName(), 'EXTERNAL_CODE' => $item->getExternalCode(), 'TYPE' => $item->getType()],
				DatasetManager::getDatasetFieldsById($datasetId)->getAll()
			);
		}
		else
		{
			$description = $source->getDescription($this->settings['dataset']['EXTERNAL_CODE']);
		}

		$headers = [];
		$externalCodes = [];
		$types = [];
		$namesMap = [];

		foreach ($description as $item)
		{
			$name = $this->prepareHeaderName($item['NAME']);
			$headers[] = $name;
			$externalCodes[] = $item['EXTERNAL_CODE'];
			$type = FieldType::tryFrom(mb_strtolower($item['TYPE'])) ?? FieldType::String;
			$types[] = $type;
			$namesMap[$name] = $item['EXTERNAL_CODE'];
		}

		$sourceData = $source->getFirstNData($this->settings['dataset']['EXTERNAL_CODE'], self::N_FIRST, $namesMap);

		$rowCollection = new RowCollection();
		foreach ($sourceData as $rowNumber => $sourceRow)
		{
			if ($rowNumber === 0)
			{
				continue;
			}

			$row = new Row();

			foreach ($sourceRow as $value)
			{
				$row->add($value);
			}

			$rowCollection->add($row);
		}

		return new ProviderDataDto(
			$headers,
			$externalCodes,
			$types,
			$rowCollection,
		);
	}

	public function setSourceId(int $sourceId): self
	{
		$this->sourceId = $sourceId;

		return $this;
	}

	public function setSettings(?array $settings): self
	{
		$this->settings = $settings;

		return $this;
	}

	/**
	 * @param string $name Column name from 1C in PascalCase.
	 *
	 * @return string Converted into snake_case - it's more readable while using one case.
	 */
	private function prepareHeaderName(string $name): string
	{
		$transliteratedName = \CUtil::translit($name, 'ru', ['change_case' => false]);

		return mb_strtoupper(preg_replace('/(?<!^)(?<!_)(?=[A-Z](?=[a-z])|(?<=[a-z])[A-Z])/u', '_', $transliteratedName));
	}
}

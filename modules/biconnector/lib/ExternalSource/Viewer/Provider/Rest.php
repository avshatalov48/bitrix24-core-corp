<?php

namespace Bitrix\BIConnector\ExternalSource\Viewer\Provider;

use Bitrix\BIConnector\ExternalSource\FieldType;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldTable;
use Bitrix\BIConnector\ExternalSource\Source;
use Bitrix\BIConnector\ExternalSource;

final class Rest implements Provider
{
	private const N_FIRST = 20;
	private int $sourceId;
	private ?array $settings = null;

	public function getData(): ProviderDataDto
	{
		/* @var ExternalSource\Source\Rest $source */
		$source = Source\Factory::getSource(ExternalSource\Type::Rest, $this->sourceId);

		$headers = [];
		$externalCodes = [];
		$types = [];
		if ($this->settings['dataset']['ID'] > 0)
		{
			$description = ExternalDatasetFieldTable::getList([
					'select' => ['CODE' => 'EXTERNAL_CODE', 'NAME', 'TYPE'],
					'filter' => [
						'=DATASET_ID' => $this->settings['dataset']['ID'],
					]
				])
				->fetchAll();
			;
		}

		if (empty($description))
		{
			$description = $source->getDescription($this->settings['dataset']['EXTERNAL_CODE']);
		}

		$codesMap = [];
		foreach ($description as $item)
		{
			$code = $item['CODE'];
			$externalCodes[] = $code;
			$headers[] = $code;
			$type = FieldType::String;
			if (!empty($item['TYPE']))
			{
				$type = FieldType::tryFrom(mb_strtolower($item['TYPE']));
			}
			$types[] = $type;
			$codesMap[$code] = $item['NAME'];
		}

		$sourceData = $source->getFirstNData($this->settings['dataset']['EXTERNAL_CODE'], self::N_FIRST, $codesMap);
		$rowCollection = new RowCollection();
		foreach ($sourceData as $sourceRow)
		{
			$row = new Row();

			foreach ($description as $column)
			{
				$row->add($sourceRow[$column['CODE']]);
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
}

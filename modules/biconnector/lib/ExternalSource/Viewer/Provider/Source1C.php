<?php

namespace Bitrix\BIConnector\ExternalSource\Viewer\Provider;

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

		$source->activateEntity($this->settings['dataset']['ID']);

		$headers = [];
		$externalCodes = [];
		$types = [];
		$description = $source->getDescription($this->settings['dataset']['EXTERNAL_CODE']);
		foreach ($description as $item)
		{
			$name = $item['NAME'];
			if (LANGUAGE_ID !== 'ru')
			{
				$name = \CUtil::translit($name, 'ru', ['change_case' => false]);
			}
			$headers[] = $this->prepareHeaderName($name);
			$externalCodes[] = $item['NAME'];
			$type = FieldType::tryFrom(mb_strtolower($item['TYPE'])) ?? FieldType::String;
			$types[] = $type;
		}

		$sourceData = $source->getFirstNData($this->settings['dataset']['EXTERNAL_CODE'], self::N_FIRST);

		$rowCollection = new RowCollection();
		foreach ($sourceData as $sourceRow)
		{
			$row = new Row();

			foreach ($description as $column)
			{
				$row->add($sourceRow[$column['NAME']]);
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
		return mb_strtolower(preg_replace('/(?<!^)(?<!_)(?=[A-ZА-Я](?=[a-zа-я])|(?<=[a-zа-я])[A-ZА-Я])/u', '_', $name));
	}
}

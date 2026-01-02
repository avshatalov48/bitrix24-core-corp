<?php

namespace Bitrix\BIConnector\ExternalSource\Viewer\Provider;

use Bitrix\Main\Localization\Loc;
use Bitrix\BIConnector\ExternalSource\Type;
use Bitrix\Biconnector\ExternalSource\FieldType;
use Bitrix\BIConnector\ExternalSource\FileReader;

final class Csv implements Provider
{
	private const N_FIRST = 20;

	private ?array $file = null;

	private array $names = [];
	private array $externalCodes = [];
	private array $types = [];
	private array $data = [];

	public function getData(): ProviderDataDto
	{
		$this->readFile();

		$rowCollection = new RowCollection();

		foreach ($this->data as $values)
		{
			$row = new Row();

			foreach ($values as $value)
			{
				$row->add($value);
			}

			$rowCollection->add($row);
		}

		return new ProviderDataDto(
			$this->names,
			$this->externalCodes,
			$this->types,
			$rowCollection
		);
	}

	public function setFile(array $file): Csv
	{
		$this->file = $file;

		return $this;
	}

	protected function readFile(): void
	{
		$settings = [
			'path' => $this->file['path'],
			'delimiter' => $this->file['delimiter'],
			'hasHeaders' => $this->file['hasHeaders'],
			'encoding' => $this->file['encoding'],
		];

		$reader = FileReader\Factory::getReader(Type::Csv, $settings);
		$rows = $reader->readFirstNRows(self::N_FIRST);

		if (empty($rows))
		{
			return;
		}

		$headers = $reader->getHeaders() ?? [];
		if (!self::isRowsValid(array_merge($headers ? [$headers] : [], $rows)))
		{
			return;
		}

		if ($this->file['hasHeaders'])
		{
			$names = $reader->getHeaders();
			foreach ($names as $i => $name)
			{
				$name = self::sanitize($name);
				if (empty($name))
				{
					$names[$i] = 'FIELD_' . $i + 1;
				}
			}

			$this->names = $names;
			$this->externalCodes = $names;
		}
		else
		{
			$rowData = $rows[0] ?? [];

			$this->externalCodes = array_keys($rowData);

			for($i = 0, $iMax = count($rowData); $i < $iMax; $i++)
			{
				$this->names[] = 'FIELD_' . $i + 1;
			}
		}

		$this->data = $rows;

		$this->types = array_fill(0, count($this->names), FieldType::String);
	}

	private static function isRowsValid(array $rows): bool
	{
		if (empty($rows))
		{
			return true;
		}

		$firstRowSize = count($rows[0]);

		foreach ($rows as $row)
		{
			if (count($row) !== $firstRowSize)
			{
				return false;
			}
		}

		return true;
	}

	private static function sanitize(string $name): array|string|null
	{
		return preg_replace('/[^\P{C}]/u', '', $name);
	}
}

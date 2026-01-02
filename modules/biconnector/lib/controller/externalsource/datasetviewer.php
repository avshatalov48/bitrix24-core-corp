<?php

namespace Bitrix\BIConnector\Controller\ExternalSource;

use Bitrix\BIConnector\ExternalSource\Type;
use Bitrix\BIConnector\ExternalSource\Viewer;
use Bitrix\BIConnector\ExternalSource\FieldType;
use Bitrix\BIConnector\ExternalSource\TypeConverter;

final class DatasetViewer
{
	private Type $type;
	private ?int $sourceId = null;
	private ?array $externalTableData = null;
	private ?array $file = null;
	private array $fields;
	private array $settings;

	public function __construct(Type $type, array $fields, array $settings)
	{
		$this->type = $type;
		$this->fields = $fields;
		$this->settings = $settings;
	}

	public function setSourceId(?int $id): self
	{
		$this->sourceId = $id;

		return $this;
	}

	public function setExternalTableData(?array $data): self
	{
		$this->externalTableData = $data;

		return $this;
	}

	public function setFile(array $file): self
	{
		$this->file = $file;

		return $this;
	}

	public function getData(bool $skipCache = false): array
	{
		$result = [];

		$viewer = $this->getViewer();

		$names = $viewer->getNames();
		$externalCodes = $viewer->getExternalCodes();
		$types = $viewer->getTypes();
		$data = $viewer->getData();

		$needUseDefaultType = $this->needUseDefaultType($data);

		for ($i = 0, $iMax = count($names); $i < $iMax; $i++)
		{
			if ($needUseDefaultType)
			{
				$type = $types[$i];
			}
			else
			{
				$type = FieldType::from($this->fields[$i]['TYPE']);
			}

			$headerData = [
				'name' => $this->prepareCode($names[$i]),
				'externalCode' => $externalCodes[$i],
				'type' => $type,
				'visible' => $this->fields[$i]['VISIBLE'] ?? true,
			];

			$result['headers'][] = $headerData;
		}

		$result['data'] = $this->convertData($data, $types);

		return $result;
	}

	public function getDataForView(): array
	{
		$data = $this->getData();

		if ($this->needFilterData())
		{
			return $this->filterDataByFields($data);
		}

		return $data;
	}

	public function getDataForSync(): array
	{
		$data = $this->getData();

		if ($this->needMergeData())
		{
			return $this->mergeDataWithFields($data);
		}

		return $data;
	}

	private function needFilterData(): bool
	{
		return $this->fields && $this->file === null;
	}

	private function filterDataByFields(array $data): array
	{
		$result = [
			'headers' => [],
			'data' => [],
		];

		$externalCodes = array_column($data['headers'], 'externalCode');
		$data['data'] = array_map(
			static function ($row) use ($externalCodes) {
				return array_combine($externalCodes, $row);
			},
			$data['data']
		);

		foreach ($this->fields as $field)
		{
			$header = [
				'name' => $field['NAME'],
				'externalCode' => $field['EXTERNAL_CODE'],
				'type' => FieldType::from($field['TYPE']),
				'visible' => $field['VISIBLE'],
			];

			if (isset($field['ID']) && (int)$field['ID'] > 0)
			{
				$header['id'] = (int)$field['ID'];
			}

			$result['headers'][] = $header;
		}

		$existingExternalCodes = array_column($this->fields, 'EXTERNAL_CODE');
		foreach ($data['data'] as $row)
		{
			$tmp = [];

			foreach ($existingExternalCodes as $externalCode)
			{
				if (isset($row[$externalCode]))
				{
					$tmp[] = $row[$externalCode];
				}
				else
				{
					$tmp[] = '';
				}
			}

			$result['data'][] = $tmp;
		}

		return $result;
	}

	private function needMergeData(): bool
	{
		return $this->fields && $this->file === null;
	}

	private function mergeDataWithFields(array $data): array
	{
		$result = [
			'headers' => [],
			'data' => [],
		];

		$externalCodes = array_column($data['headers'], 'externalCode');
		$data['headers'] = array_combine($externalCodes, $data['headers']);
		$data['data'] = array_map(
			static function (array $row) use ($externalCodes) {
				return array_combine($externalCodes, $row);
			},
			$data['data']
		);

		$fields = array_combine(array_column($this->fields, 'EXTERNAL_CODE'), $this->fields);

		$newFields = [];
		foreach ($data['headers'] as $externalCode => $header)
		{
			$field = $fields[$externalCode] ?? null;
			if ($field)
			{
				$tmp = [
					'name' => $field['NAME'],
					'externalCode' => $field['EXTERNAL_CODE'],
					'type' => FieldType::from($field['TYPE']),
					'visible' => $field['VISIBLE'],
				];

				if (isset($field['ID']) && (int)$field['ID'] > 0)
				{
					$tmp['id'] = (int)$field['ID'];
				}

				$result['headers'][$externalCode] = $tmp;
			}
			else
			{
				$newFields[$externalCode] = $header;
			}
		}

		$currentExternalCodes = array_flip(array_column($this->fields, 'EXTERNAL_CODE'));

		if ($newFields)
		{
			$result['headers'] = array_merge($result['headers'], $newFields);

			$newExternalCodes = array_flip(array_column($newFields, 'externalCode'));
			$currentExternalCodes = array_merge($currentExternalCodes, $newExternalCodes);
		}

		$currentExternalCodes = array_filter($currentExternalCodes, function ($code) use ($result) {
			return array_key_exists($code, $result['headers']);
		}, ARRAY_FILTER_USE_KEY );
		$result['headers'] = array_values(array_merge($currentExternalCodes, $result['headers']));

		$result['data'] = array_map(
			static function (array $row) use ($currentExternalCodes) {
				return array_merge($currentExternalCodes, $row);
			},
			$data['data']
		);
		$result['data'] = array_map('array_values', $result['data']);

		return $result;
	}

	private function getViewer(): Viewer\Viewer
	{
		$viewerBuilder = new Viewer\ViewerBuilder();
		$viewerBuilder->setType($this->type);

		if ($this->file)
		{
			$viewerBuilder->setFile($this->file);
		}

		if ($this->sourceId && $this->externalTableData)
		{
			$viewerBuilder
				->setSourceId($this->sourceId)
				->setExternalTableData($this->externalTableData)
				->setSettings($this->settings)
			;
		}

		return $viewerBuilder->build();
	}

	private function convertData(array $data, array $types): array
	{
		$needUseDefaultType = $this->needUseDefaultType($data);

		$formats = [];
		foreach ($this->settings as $setting)
		{
			$formats[$setting['TYPE']] = $setting['FORMAT'];
		}

		foreach ($data as $rowIndex => $rowValue)
		{
			foreach ($rowValue as $columnIndex => $columnValue)
			{
				$type = $needUseDefaultType
					? $types[$columnIndex]
					: FieldType::from($this->fields[$columnIndex]['TYPE']);
				switch ($type)
				{
					case FieldType::Int:
						$data[$rowIndex][$columnIndex] = TypeConverter::convertToInt($columnValue);

						break;

					case FieldType::Double:
						$delimiter = $formats[FieldType::Double->value];
						$data[$rowIndex][$columnIndex] = TypeConverter::convertToDouble(
							$columnValue,
							delimiter: $delimiter
						);

						break;

					case FieldType::Date:
						$format = $formats[FieldType::Date->value];
						$value = TypeConverter::convertToDate(
							$columnValue,
							$format
						);
						if ($value)
						{
							$data[$rowIndex][$columnIndex] = $value->format('Y-m-d');
						}
						else
						{
							$data[$rowIndex][$columnIndex] = '';
						}

						break;

					case FieldType::DateTime:
						$format = $formats[FieldType::DateTime->value];
						$value = TypeConverter::convertToDateTime(
							$columnValue,
							$format
						);
						if ($value)
						{
							$data[$rowIndex][$columnIndex] = $value->format('Y-m-d H:i:s');
						}
						else
						{
							$data[$rowIndex][$columnIndex] = '';
						}

						break;

					case FieldType::Money:
						$delimiter = $formats[FieldType::Money->value];
						$data[$rowIndex][$columnIndex] = self::formatMoney(
							TypeConverter::convertToMoney(
								$columnValue,
								delimiter: $delimiter
							)
						);

						break;

					case FieldType::String:
					default:
						$data[$rowIndex][$columnIndex] = TypeConverter::convertToString($columnValue);

						break;
				}
			}
		}

		return $data;
	}

	private function needUseDefaultType(array $data): bool
	{
		if (empty($data))
		{
			return true;
		}

		if (count($data[0]) !== count($this->fields))
		{
			return true;
		}

		if ($this->type === Type::Rest || $this->type === Type::Source1C)
		{
			return true;
		}

		return false;
	}

	private static function formatMoney(float $value): string
	{
		return number_format($value, 2, '.', '');
	}

	private function prepareCode(string $name): string
	{
		if ($this->type === Type::Rest)
		{
			return $name;
		}

		$transliteratedName = \CUtil::translit($name, LANGUAGE_ID, ['change_case' => 'U']);
		if ($transliteratedName === '_')
		{
			$transliteratedName = '';
		}

		return $transliteratedName;
	}
}

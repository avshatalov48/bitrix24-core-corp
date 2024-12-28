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

	public function getData(): array
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
				'name' => $this->fields[$i]['NAME'] ?? self::prepareCode($names[$i]),
				'externalCode' => $externalCodes[$i],
				'type' => $type,
				'visible' => $this->fields[$i]['VISIBLE'] ?? true,
			];
			if (isset($this->fields[$i]['ID']))
			{
				$headerData['id'] = (int)$this->fields[$i]['ID'];
			}
			$result['headers'][] = $headerData;
		}

		$result['data'] = $this->convertData($data);

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

	private function convertData(array $data): array
	{
		$needUseDefaultType = $this->needUseDefaultType($data);

		$formats = [];
		foreach ($this->settings as $setting)
		{
			$formats[$setting['TYPE']] = $setting['FORMAT'];
		}

		foreach ($data as $rowNumber => $rowValue)
		{
			foreach ($rowValue as $columnNumber => $columnValue)
			{
				if ($needUseDefaultType)
				{
					$data[$rowNumber][$columnNumber] = TypeConverter::convertToString($columnValue);
				}
				else
				{
					$type = FieldType::from($this->fields[$columnNumber]['TYPE']);
					switch ($type)
					{
						case FieldType::Int:
							$data[$rowNumber][$columnNumber] = TypeConverter::convertToInt($columnValue);

							break;
						case FieldType::String:
							$data[$rowNumber][$columnNumber] = TypeConverter::convertToString($columnValue);

							break;

						case FieldType::Double:
							$delimiter = $formats[FieldType::Double->value];
							$data[$rowNumber][$columnNumber] = TypeConverter::convertToDouble(
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
								$data[$rowNumber][$columnNumber] = $value->format('Y-m-d');
							}
							else
							{
								$data[$rowNumber][$columnNumber] = '';
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
								$data[$rowNumber][$columnNumber] = $value->format('Y-m-d H:i:s');
							}
							else
							{
								$data[$rowNumber][$columnNumber] = '';
							}

							break;

						case FieldType::Money:
							$delimiter = $formats[FieldType::Money->value];
							$data[$rowNumber][$columnNumber] = self::formatMoney(
								TypeConverter::convertToMoney(
									$columnValue,
									delimiter: $delimiter
								)
							);

							break;
					}
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

		return false;
	}

	private static function formatMoney(float $value): string
	{
		return number_format($value, 2, '.', '');
	}

	private static function prepareCode(string $name): string
	{
		$transliteratedName = \CUtil::translit($name, LANGUAGE_ID, ['change_case' => 'U']);
		if ($transliteratedName === '_')
		{
			$transliteratedName = '';
		}

		return $transliteratedName;
	}
}

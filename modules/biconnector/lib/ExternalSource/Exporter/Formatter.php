<?php

namespace Bitrix\BIConnector\ExternalSource\Exporter;

use Bitrix\BIConnector\ExternalSource\FieldType;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldCollection;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormatCollection;
use Bitrix\BIConnector\ExternalSource\TypeConverter;

class Formatter
{
	private array $fields;
	private array $formats;

	public function __construct(ExternalDatasetFieldCollection $fields, ExternalDatasetFieldFormatCollection $formats)
	{
		$preparedFields = [];
		foreach ($fields as $field)
		{
			$preparedFields[$field->getName()] = $field->getType();
		}
		$this->fields = $preparedFields;

		$preparedFormats = [];
		foreach ($formats as $format)
		{
			$preparedFormats[$format->getType()] = $format->getFormat();
		}
		$this->formats = $preparedFormats;
	}

	public function formatRow(array $row): array
	{
		$result = [];

		foreach ($row as $name => $value)
		{
			$result[$name] = match ($this->fields[$name]) {
				FieldType::DateTime->value => TypeConverter::convertDateTimeToString($value, $this->formats['datetime']),
				FieldType::Date->value => TypeConverter::convertDateToString($value, $this->formats['date']),
				FieldType::Double->value => TypeConverter::formatDoubleString($value, $this->formats['double']),
				FieldType::Money->value => TypeConverter::formatDoubleString($value, $this->formats['money']),
				default => $value,
			};
		}

		return $result;
	}
}

<?php

namespace Bitrix\BIConnector\ExternalSource\Exporter;

use Bitrix\Main\SystemException;

final class WriterFactory
{
	public static function getWriter(ExportType $type): Writer
	{
		return match ($type)
		{
			ExportType::Csv => new CsvWriter(),
			default => throw new SystemException("Unknown type {$type->value}"),
		};
	}
}

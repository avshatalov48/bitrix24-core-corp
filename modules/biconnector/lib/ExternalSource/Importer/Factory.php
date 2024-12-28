<?php

namespace Bitrix\BIConnector\ExternalSource\Importer;

use Bitrix\Main\SystemException;
use Bitrix\BIConnector\ExternalSource\Type;

final class Factory
{
	public static function getImporter(Type $type, Settings $settings): Importer
	{
		return match ($type)
		{
			Type::Csv => new CsvImporter($settings),
			default => throw new SystemException("Unknown type {$type->value}"),
		};
	}
}
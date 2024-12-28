<?php

namespace Bitrix\BIConnector\ExternalSource\FileReader;

use Bitrix\Main\SystemException;
use Bitrix\Main\ArgumentException;
use Bitrix\BIConnector\ExternalSource;

final class Factory
{
	public static function getReader(ExternalSource\Type $type, array $settings): Base
	{
		return match ($type)
		{
			ExternalSource\Type::Csv => self::createCsvReader($settings),
			default => throw new SystemException("Unknown type {$type->value}"),
		};
	}

	private static function createCsvReader(array $settings): Csv\Reader
	{
		if (!isset($settings['path'], $settings['delimiter'], $settings['hasHeaders'], $settings['encoding']))
		{
			throw new ArgumentException('Setting "path", "delimiter", "hasHeaders" and "encoding" must be set', 'setting');
		}

		$readerSettings = new Csv\Settings(
			path: $settings['path'],
			delimiter: $settings['delimiter'],
			hasHeaders: $settings['hasHeaders'],
			encoding: $settings['encoding'],
		);

		return new Csv\Reader($readerSettings);
	}
}

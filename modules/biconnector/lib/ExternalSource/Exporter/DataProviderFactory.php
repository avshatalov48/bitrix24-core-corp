<?php

namespace Bitrix\BIConnector\ExternalSource\Exporter;

use Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset;
use Bitrix\BIConnector\ExternalSource\Source\Csv;
use Bitrix\BIConnector\ExternalSource\Type;
use Bitrix\Main\SystemException;

class DataProviderFactory
{
	public static function getDataProvider(ExternalDataset $dataset): DataProvider
	{
		return match ($dataset->getEnumType())
		{
			Type::Csv => new TableDataProvider(Csv::TABLE_NAME_PREFIX . $dataset->getName()),
			default => throw new SystemException("Unsupported type {$dataset->getEnumType()->value}"),
		};
	}
}

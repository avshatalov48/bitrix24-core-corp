<?php

namespace Bitrix\BIConnector\ExternalSource\Dataset;

use Bitrix\Main;
use Bitrix\BIConnector;

final class Factory
{
	public static function getDataset(
		BIConnector\ExternalSource\Internal\ExternalDataset $dataset,
		Main\DB\Connection $dataConnection,
		string $languageId = null
	): Base
	{
		$type = $dataset->getEnumType();

		return match ($type)
		{
			BIConnector\ExternalSource\Type::Csv => Csv::createDataset($dataset, $dataConnection, $languageId),
			BIConnector\ExternalSource\Type::Source1C => Source1C::createDataset($dataset, $dataConnection, $languageId),
			default => throw new Main\SystemException("Unknown type {$type->value}"),
		};
	}
}

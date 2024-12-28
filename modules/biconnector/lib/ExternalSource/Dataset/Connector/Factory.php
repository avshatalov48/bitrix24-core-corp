<?php

namespace Bitrix\BIConnector\ExternalSource\Dataset\Connector;

use Bitrix\Main\SystemException;
use Bitrix\BIConnector\DataSourceConnector\FieldCollection;
use Bitrix\BIConnector\DataSourceConnector;
use Bitrix\BIConnector\ExternalSource\Type;

final class Factory
{
	public static function getConnector(
		Type $type,
		string $name,
		FieldCollection $fields,
		array $datasetInfo
	): DataSourceConnector\Connector\Base
	{
		return match ($type)
		{
			Type::Csv => new Csv($name, $fields, $datasetInfo),
			Type::Source1C => new Source1C($name, $fields, $datasetInfo),
			default => throw new SystemException("Unknown type {$type->value}"),
		};
	}
}

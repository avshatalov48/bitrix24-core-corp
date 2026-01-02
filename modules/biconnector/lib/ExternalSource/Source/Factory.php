<?php

namespace Bitrix\BIConnector\ExternalSource\Source;

use Bitrix\Main\SystemException;
use Bitrix\Main\ArgumentException;
use Bitrix\BIConnector\ExternalSource\Type;

final class Factory
{
	public static function getSource(Type $type, int $sourceId, ?int $datasetId = null): Base
	{
		self::checkParams($type, $datasetId);

		$id = self::resolveId($type, $sourceId, $datasetId);

		return match ($type)
		{
			Type::Csv => new Csv($id),
			Type::Source1C => new Source1C($id),
			Type::Rest => new Rest($id),
			default => throw new SystemException("Unknown type {$type->value}"),
		};
	}

	private static function resolveId(Type $type, int $sourceId, ?int $datasetId = null): ?int
	{
		$id = $sourceId;
		if ($type === Type::Csv)
		{
			$id = $datasetId;
		}

		return $id;
	}

	private static function checkParams(Type $type, ?int $datasetId = null): void
	{
		if ($type === Type::Csv && (int)$datasetId <= 0)
		{
			throw new ArgumentException('Must be greater than zero.', 'datasetId');
		}
	}
}

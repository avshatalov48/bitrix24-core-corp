<?php

namespace Bitrix\BIConnector\ExternalSource\Exporter;

use Bitrix\Main\Application;

class TableDataProvider implements DataProvider
{
	public function __construct(
		private string $tableName
	)
	{
	}

	public function getTotalSize(): int
	{
		return (int)Application::getInstance()->getConnection()->query(
			sprintf(
				'SELECT count(*) as COUNT FROM `%s`',
				$this->tableName
			)
		)->fetch()['COUNT'];
	}

	public function fetchChunk(int $chunkSize, int $chunkOffset): iterable
	{
		$res = Application::getInstance()->getConnection()->query(
			sprintf(
				"SELECT * FROM `%s` LIMIT %d OFFSET %d",
				$this->tableName,
				$chunkSize,
				$chunkOffset,
			)
		);

		while ($row = $res->fetch())
		{
			yield $row;
		}
	}
}

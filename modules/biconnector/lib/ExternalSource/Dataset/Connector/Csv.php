<?php

namespace Bitrix\BIConnector\ExternalSource\Dataset\Connector;

use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\BIConnector\DataSourceConnector\Connector\Sql;
use Bitrix\BIConnector\Manager;

final class Csv extends Sql
{
	protected const ANALYTIC_TAG_DATASET = 'CSV';

	/**
	 * @param array $parameters
	 * @param int $limit
	 * @param array $dateFormats
	 *
	 * @return \Generator
	 */
	public function query(
		array $parameters,
		int $limit,
		array $dateFormats = []
	): \Generator
	{
		$result = new Result();

		$manager = Manager::getInstance();
		$connection = $manager->getDatabaseConnection();
		$data = $this->getFormattedData($parameters, $dateFormats);

		$resultQuery = $connection->biQuery($data['sql']);
		if (!$resultQuery)
		{
			$result->addError(
				new Error(
					'SQL_ERROR',
					0,
					['description' => $connection->getErrorMessage()]
				)
			);

			return $result;
		}

		$count = 0;
		while ($row = $resultQuery->fetch())
		{
			if ($limit && $count === $limit)
			{
				/** The issue is 0205035 */
				continue;
			}

			yield $row;
			$count++;
		}

		return $result;
	}
}

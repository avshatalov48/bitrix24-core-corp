<?php

namespace Bitrix\BIConnector\ExternalSource\Dataset\Connector;

use Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetTable;
use Bitrix\BIConnector\DataSourceConnector\Connector\Base;
use Bitrix\BIConnector\ExternalSource\Source;
use Bitrix\BIConnector\ExternalSource;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

final class Rest extends Base
{
	protected const ANALYTIC_TAG_DATASET = 'REST';

	/**
	 * @param array $parameters
	 * @param int $limit
	 * @param array $dateFormats
	 * @return \Generator
	 */
	public function query(
		array $parameters,
		int $limit,
		array $dateFormats = []
	): \Generator
	{
		$result = new Result();

		$data = $this->getData($parameters, $dateFormats);

		$tableName = $this->getName();

		$dataset = ExternalDatasetTable::getList([
			'filter' => ['=NAME' => $tableName],
			'limit' => 1
		])
			->fetchObject()
		;
		$sourceId = $dataset->getSourceId();

		$queryFields = [
			'select' => array_column($data->getConnectorData()->schema, 'NAME'),
			'filter' => $data->getConnectorData()->filter,
			'limit' => $limit,
		];

		try
		{
			/* @var ExternalSource\Source\Rest $source */
			$source = Source\Factory::getSource(ExternalSource\Type::Rest, $sourceId);
			yield $source->getData($dataset->getExternalCode(), $queryFields);
		}
		catch (\Exception $e)
		{
			$result->addError(new Error($e->getMessage()));
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function isFulfilledOutput(): bool
	{
		return true;
	}
}

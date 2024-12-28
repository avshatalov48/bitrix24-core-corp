<?php

namespace Bitrix\BIConnector\ExternalSource\Dataset\Connector;

use Bitrix\BIConnector\ExternalSource\DatasetManager;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetTable;
use Bitrix\BIConnector\DataSourceConnector\Connector\Base;
use Bitrix\BIConnector\ExternalSource\Source;
use Bitrix\BIConnector\ExternalSource;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type;

final class Source1C extends Base
{
	protected const ANALYTIC_TAG_DATASET = '1C';

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
		$dataset = ExternalDatasetTable::getList(['filter' => ['=NAME' => $tableName], 'limit' => 1])->fetchObject();
		$sourceId = $dataset->getSourceId();

		/* @var ExternalSource\Source\Source1C $source */
		$source = Source\Factory::getSource(ExternalSource\Type::Source1C, $sourceId);

		$queryFields = [
			'select' => $data->getConnectorData()->schema,
			'filter' => $data->getConnectorData()->filter,
			'limit' => $limit,
		];

		try
		{
			$source->initDatasetFields($dataset->getName());
			$externalData = $source->getData($dataset->getExternalCode(), $queryFields);
		}
		catch (\Exception $e)
		{
			$result->addError(new Error($e->getMessage()));

			return $result;
		}

		$fieldCodeMap = $this->getFieldCodesMap($dataset->getId());

		$counter = 0;
		foreach ($externalData as $externalRow)
		{
			$resultRow = [];
			foreach ($queryFields['select'] as $selectField)
			{
				if ($selectField['TYPE'] === 'DATETIME')
				{
					$value = $externalRow[$fieldCodeMap[$selectField['NAME']]];
					if ($value)
					{
						$date = new Type\DateTime($value, DATE_ATOM);

						// Trino handles earliest dates not correctly - https://github.com/trinodb/trino/issues/23904
						if ($date <= new Type\DateTime('1900-01-01', 'Y-m-d'))
						{
							$resultRow[] = null;
						}
						else
						{
							$resultRow[] = $date->format('Y-m-d H:i:s');
						}
					}
					else
					{
						$resultRow[] = null;
					}
				}
				else
				{
					$resultRow[] = $externalRow[$fieldCodeMap[$selectField['NAME']]];
				}
			}

			if ($limit && $counter === $limit)
			{
				break;
			}

			yield $resultRow;
			$counter++;
		}

		return $result;
	}

	/**
	 * @param int $datasetId
	 *
	 * @return array Map of codes: internal column name -> 1C column name
	 */
	private function getFieldCodesMap(int $datasetId): array
	{
		$result = [];

		$datasetFieldCollection = DatasetManager::getDatasetFieldsById($datasetId)->getAll();
		foreach ($datasetFieldCollection as $field)
		{
			$result[$field->getName()] = $field->getExternalCode();
		}

		return $result;
	}
}

<?php

namespace Bitrix\BIConnector\ExternalSource\Dataset\Connector;

use Bitrix\BIConnector\DataSourceConnector\ConnectorDataResult;
use Bitrix\BIConnector\DataSourceConnector\ConnectorDto;
use Bitrix\BIConnector\ExternalSource\DatasetManager;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetTable;
use Bitrix\BIConnector\DataSourceConnector\Connector\Base;
use Bitrix\BIConnector\ExternalSource\Source;
use Bitrix\BIConnector\ExternalSource;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

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

		$fieldCodeMap = $this->getFieldCodesMap($dataset->getId());
		$fields = $data->getConnectorData()->schema;
		$queryFields = [
			'select' => array_column($fields, 'NAME'),
			'columnNames' => $fieldCodeMap,
			'filter' => $data->getConnectorData()->filter,
			'limit' => $limit,
		];

		try
		{
			$source->initDatasetFields($dataset->getName());
			yield $source->getData($dataset->getExternalCode(), $queryFields);
		}
		catch (\Exception $e)
		{
			$result->addError(new Error($e->getMessage()));

			return $result;
		}

		return $result;
	}

	/**
	 * @param array $parameters
	 * @param array $dateFormats
	 *
	 * @return ConnectorDataResult
	 */
	public function getData(array $parameters, array $dateFormats = []): ConnectorDataResult
	{
		$result = new ConnectorDataResult();

		$tableInfo = $this->rawInfo;
		$tableFields = $tableInfo['FIELDS'] ?? [];

		$canBeFiltered = true;
		$filters = [];
		if (isset($parameters['dateRange']) && is_array($parameters['dateRange']))
		{
			$timeFilterColumn = $parameters['configParams']['timeFilterColumn'] ?? '';
			$filters = $this->applyDateFilter($filters, $parameters['dateRange'], $timeFilterColumn);
		}

		if (isset($parameters['dimensionsFilters']) && is_array($parameters['dimensionsFilters']))
		{
			$filters = $this->applyDimensionsFilters($filters, $canBeFiltered, $parameters['dimensionsFilters']);
		}

		$selectedFields = [];
		if (isset($parameters['fields']) && is_array($parameters['fields']))
		{
			foreach ($parameters['fields'] as $field)
			{
				$fieldName = trim($field['name'], " \t\n\r");
				if ($fieldName && isset($tableFields[$fieldName]))
				{
					$selectedFields[$fieldName] = $tableFields[$fieldName];
				}
			}
			if (!$selectedFields)
			{
				$result->addError(new Error('EMPTY_SELECT_FIELDS_LIST'));

				return $result;
			}
		}
		else
		{
			$selectedFields = $tableFields;
		}

		$schemaFields = $this->getFields()->toArray();

		$schema = [];
		foreach (array_keys($selectedFields) as $fieldName)
		{
			foreach ($schemaFields as $fieldInfo)
			{
				if ($fieldName === $fieldInfo['ID'])
				{
					$schema[] = $fieldInfo;
				}
			}
		}

		$dto = new ConnectorDto(
			$schema,
			'',
			[],
			$filters,
			true,
			[]
		);

		$result->setConnectorData($dto);

		return $result;
	}

	/**
	 * applyDateFilter
	 *
	 * @param array $sqlWhere Modified where.
	 * @param array $dateRange Filters from input.
	 * @param string $timeFilterColumn Column from input.
	 *
	 * @return array
	 */
	protected function applyDateFilter(
		array $sqlWhere,
		array $dateRange,
		string $timeFilterColumn = ''
	): array
	{
		$tableFields = $this->rawInfo['FIELDS'] ?? [];

		$filterColumnName = false;
		if (
			$timeFilterColumn
			&& array_key_exists($timeFilterColumn, $tableFields)
			&& in_array($tableFields[$timeFilterColumn]['FIELD_TYPE'], ['date', 'datetime'], true)
		)
		{
			$filterColumnName = $timeFilterColumn;
		}
		else
		{
			foreach ($tableFields as $fieldName => $fieldInfo)
			{
				if (in_array($fieldInfo['FIELD_TYPE'], ['date', 'datetime'], true))
				{
					$filterColumnName = $fieldName;
					break;
				}
			}
		}

		if (!$filterColumnName)
		{
			return $sqlWhere;
		}

		if (isset($dateRange['startDate']))
		{
			$sqlWhere[] = [
				'COLUMN' => $filterColumnName,
				'OPERATOR' => 'DATE_GREATER_THAN_OR_EQUAL',
				'VALUE' => (new DateTime($dateRange['startDate'], 'Y-m-d'))->format("Y-m-d\TH:i:s"),
			];
		}

		if (isset($dateRange['endDate']))
		{
			$endDate = (new DateTime($dateRange['endDate'], 'Y-m-d'));
			$endDate->add('+23 hours +59 minutes +59 seconds');
			if ($endDate > new DateTime('31.12.3999'))
			{
				// 1c doesn't allow years after 3999 in filters
				$endDate = new DateTime('31.12.3999 23:59:59');
			}
			$sqlWhere[] = [
				'COLUMN' => $filterColumnName,
				'OPERATOR' => 'DATE_LESS_THAN_OR_EQUAL',
				'VALUE' => $endDate->format("Y-m-d\TH:i:s"),
			];
		}

		return $sqlWhere;
	}

	/**
	 * applyDimensionsFilters
	 *
	 * @param array $sqlWhere Modified where.
	 * @param bool &$canBeFiltered Return flag. Not used here.
	 * @param array $dimensionsFilters Filters from input.
	 *
	 * @return array
	 */
	protected function applyDimensionsFilters($sqlWhere, &$canBeFiltered, $dimensionsFilters): array
	{
		$tableFields = $this->rawInfo['FIELDS'] ?? [];

		foreach ($dimensionsFilters as $topFilter)
		{
			foreach ($topFilter as $subFilter)
			{
				if ($subFilter['fieldName'] && isset($tableFields[$subFilter['fieldName']]))
				{
					$negate = $subFilter['type'] === 'EXCLUDE';
					switch ($subFilter['operator'])
					{
						case 'EQUALS':
							$sqlWhere[] = [
								'COLUMN' => $subFilter['fieldName'],
								'OPERATOR' => $negate ? "NOT_{$subFilter['operator']}" : $subFilter['operator'],
								'VALUE' => $subFilter['values'][0],
							];
							break;
						case 'IN_LIST':
						case 'BETWEEN':
						case 'NUMERIC_GREATER_THAN':
						case 'NUMERIC_GREATER_THAN_OR_EQUAL':
						case 'NUMERIC_LESS_THAN':
						case 'NUMERIC_LESS_THAN_OR_EQUAL':
							$sqlWhere[] = [
								'COLUMN' => $subFilter['fieldName'],
								'OPERATOR' => $negate ? "NOT_{$subFilter['operator']}" : $subFilter['operator'],
								'VALUE' => $subFilter['values'],
							];
							break;
						case 'IS_NULL':
							$sqlWhere[] = [
								'COLUMN' => $subFilter['fieldName'],
								'OPERATOR' => $negate ? "NOT_{$subFilter['operator']}" : $subFilter['operator'],
								'VALUE' => null,
							];
							break;
					}
				}
			}
		}

		return $sqlWhere;
	}

	/**
	 * @return bool
	 */
	public function isFulfilledOutput(): bool
	{
		return true;
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

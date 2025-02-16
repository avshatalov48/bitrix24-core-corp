<?php

namespace Bitrix\BIConnector\DataSourceConnector\Connector;

use Bitrix\BIConnector\DataSourceConnector\ConnectorDataResult;
use Bitrix\BIConnector\DataSourceConnector\ConnectorDto;
use Bitrix\BIConnector\DataSourceConnector\FieldCollection;
use Bitrix\BIConnector\DataSourceConnector\QueryResult;
use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Main\Error;

abstract class Base
{
	protected const ANALYTIC_TAG_DATASET = 'other';

	public function __construct(
		protected string $name,
		protected FieldCollection $fields,
		public readonly array $rawInfo
	)
	{
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return FieldCollection
	 */
	public function getFields(): FieldCollection
	{
		return $this->fields;
	}

	/**
	 * @param array $parameters
	 * @param array $dateFormats
	 *
	 * @return array|string[]
	 */
	public function getFormattedData(array $parameters, array $dateFormats = []): array
	{
		$connectorDataResult = $this->getData($parameters, $dateFormats);
		if (!$connectorDataResult->isSuccess())
		{
			return [
				'error' => $connectorDataResult->getErrorMessages()[0],
			];
		}

		$connectorData = $connectorDataResult->getConnectorData();

		return [
			'schema' => $connectorData->schema,
			'sql' => $connectorData->query,
			'onAfterFetch' => $connectorData->queryRowCallbacks,
			'filtersApplied' => $connectorData->filtersApplied,
			'where' => $connectorData->filter,
			'shadowFields' => $connectorData->shadowFields,
		];
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
		$sqlWhere = $tableInfo['FILTER'] ?? [];

		if (isset($parameters['dateRange']) && is_array($parameters['dateRange']))
		{
			if (isset($parameters['configParams']['timeFilterColumn']))
			{
				$timeFilterColumn = $parameters['configParams']['timeFilterColumn'];
			}
			elseif (isset($parameters['configParams']['timefiltercolumn']))
			{
				$timeFilterColumn = $parameters['configParams']['timefiltercolumn'];
			}
			else
			{
				$timeFilterColumn = '';
			}

			$sqlWhere = $this->applyDateFilter($sqlWhere, $parameters['dateRange'], $timeFilterColumn);
		}

		// https://developers.google.com/datastudio/connector/filters?hl=ru
		if (isset($parameters['dimensionsFilters']) && is_array($parameters['dimensionsFilters']))
		{
			$sqlWhere = $this->applyDimensionsFilters($sqlWhere, $canBeFiltered, $parameters['dimensionsFilters']);
		}

		$queryWhere = new \CBIConnectorSqlBuilder;
		$queryWhere->SetFields($tableFields);
		if (isset($tableInfo['FILTER_FIELDS']))
		{
			$queryWhere->AddFields($tableInfo['FILTER_FIELDS']);
		}

		$strQueryWhere = '';
		if ($canBeFiltered && $sqlWhere)
		{
			$strQueryWhere = $queryWhere->GetQuery($sqlWhere);
		}

		$selectedFields = [];
		$groupFields = [];
		if (isset($parameters['fields']) && is_array($parameters['fields']))
		{
			foreach ($parameters['fields'] as $field)
			{
				$fieldName = trim($field['name'], " \t\n\r");
				if ($fieldName && isset($tableFields[$fieldName]))
				{
					$tableField = $tableFields[$fieldName];
					if (
						(!isset($field['forFilterOnly']) || !$field['forFilterOnly'])
						|| !($strQueryWhere && $canBeFiltered)
					)
					{
						if (isset($tableField['GROUP_KEY']))
						{
							$groupKey = $tableField['GROUP_KEY'];
							$groupFields[$groupKey] = $tableFields[$groupKey];
							$groupFields[$groupKey]['ID'] = $groupKey;
						}
						$selectedFields[$fieldName] = $tableField;
					}
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

		$primaryFields = [];
		if ($groupFields)
		{
			foreach ($tableFields as $fieldName => $tableField)
			{
				if (isset($tableField['IS_PRIMARY']) && $tableField['IS_PRIMARY'] == 'Y')
				{
					$tableField['ID'] = $fieldName;
					$primaryFields[$fieldName] = $tableField;
				}
			}
		}

		foreach ($groupFields as $groupKey => $_)
		{
			if (isset($selectedFields[$groupKey]))
			{
				unset($groupFields[$groupKey]);
			}
		}

		foreach ($primaryFields as $primaryKey => $_)
		{
			if (isset($selectedFields[$primaryKey]))
			{
				unset($primaryFields[$primaryKey]);
			}
		}

		$shadowFields = array_merge($primaryFields, $groupFields);

		$queryWhere->SetSelect(array_merge($selectedFields, $shadowFields), $dateFormats);

		$i = 0;
		$fetchCallbacks = [];
		foreach (array_merge($selectedFields, $shadowFields) as $fieldInfo)
		{
			if (isset($fieldInfo['CALLBACK']))
			{
				$fetchCallbacks[$i] = $fieldInfo['CALLBACK'];
			}
			elseif ($fieldInfo['FIELD_TYPE'] === 'int')
			{
				$fetchCallbacks[$i] = static fn ($value) => $value === null ? null : (int)$value;
			}

			$i++;
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

		if (isset($tableInfo['DICTIONARY']))
		{
			foreach ($tableInfo['DICTIONARY'] as $dictionaryId)
			{
				if (!\Bitrix\BIConnector\DictionaryManager::validateCache($dictionaryId))
				{
					$result->addError(new Error("DICTIONARY_{$dictionaryId}_UPDATE"));

					return $result;
				}
			}
		}

		$query = $this->getPrintedQuery(
			$queryWhere,
			$tableInfo,
			($canBeFiltered && $sqlWhere) ? $sqlWhere : null,
			$parameters
		);

		$dto = new ConnectorDto(
			$schema,
			$query,
			$fetchCallbacks,
			$sqlWhere,
			($strQueryWhere && $canBeFiltered),
			$shadowFields
		);

		$result->setConnectorData($dto);

		return $result;
	}

	/**
	 * @param \CBIConnectorSqlBuilder $builder
	 * @param array $tableInfo
	 * @param array|null $where
	 *
	 * @return string
	 */
	protected function getPrintedQuery(
		\CBIConnectorSqlBuilder $builder,
		array $tableInfo,
		?array $where = null,
		?array $queryParameters = null
	): string
	{
		return '';
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
	private function applyDateFilter(
		array $sqlWhere,
		array $dateRange,
		string $timeFilterColumn = ''
	): array
	{
		$tableFields = $this->rawInfo['FIELDS'] ?? [];

		$startDate =
			array_key_exists('startDate', $dateRange)
				? MakeTimeStamp($dateRange['startDate'], 'YYYY-MM-DD')
				: false
		;

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

		if ($startDate)
		{
			$sqlWhere['>=' . $filterColumnName] = ConvertTimeStamp($startDate, 'SHORT');
		}

		$endDate =
			array_key_exists('endDate', $dateRange)
				? MakeTimeStamp($dateRange['endDate'], 'YYYY-MM-DD')
				: false
		;
		if ($endDate)
		{
			$endDate += 23 * 3600 + 59 * 60 + 59;
			$sqlWhere['<=' . $filterColumnName] = ConvertTimeStamp($endDate, 'FULL');
		}

		return $sqlWhere;
	}

	/**
	 * applyDimensionsFilters
	 *
	 * @param array $sqlWhere Modified where.
	 * @param bool &$canBeFiltered Return flag.
	 * @param array $dimensionsFilters Filters from input.
	 *
	 * @return array
	 */
	private function applyDimensionsFilters($sqlWhere, &$canBeFiltered, $dimensionsFilters): array
	{
		$tableFields = $this->rawInfo['FIELDS'] ?? [];

		foreach ($dimensionsFilters as $topFilter)
		{
			$andFilter = [
				'LOGIC' => 'OR',
			];

			foreach ($topFilter as $subFilter)
			{
				if ($subFilter['fieldName'] && isset($tableFields[$subFilter['fieldName']]))
				{
					if ($tableFields[$subFilter['fieldName']]['FIELD_TYPE'] === 'datetime')
					{
						if (is_array($subFilter['values']))
						{
							foreach ($subFilter['values'] as $i => $value)
							{
								$subFilter['values'][$i] = ConvertTimeStamp(strtotime($value), 'FULL');
							}
						}
						else
						{
							$subFilter['values'] = ConvertTimeStamp(strtotime($subFilter['values']), 'FULL');
						}
					}
					elseif ($tableFields[$subFilter['fieldName']]['FIELD_TYPE'] === 'date')
					{
						if (is_array($subFilter['values']))
						{
							foreach ($subFilter['values'] as $i => $value)
							{
								$subFilter['values'][$i] = ConvertTimeStamp(strtotime($value), 'SHORT');
							}
						}
						else
						{
							$subFilter['values'] = ConvertTimeStamp(strtotime($subFilter['values']), 'SHORT');
						}
					}

					$negate = $subFilter['type'] === 'EXCLUDE' ? '!' : '';
					switch ($subFilter['operator'])
					{
						case 'EQUALS':
						case 'IN_LIST':
							$andFilter[] = [$negate . '=' . $subFilter['fieldName'] => $subFilter['values']];
							break;
						case 'CONTAINS':
							$andFilter[] = [$negate . '%' . $subFilter['fieldName'] => $subFilter['values']];
							break;
						case 'REGEXP_PARTIAL_MATCH':
						case 'REGEXP_EXACT_MATCH':
							$canBeFiltered = false;
							break;
						case 'IS_NULL':
							$andFilter[] = [$negate . '=' . $subFilter['fieldName'] => false];
							break;
						case 'BETWEEN':
							$andFilter[] = [$negate . '><' . $subFilter['fieldName'] => $subFilter['values']];
							break;
						case 'NUMERIC_GREATER_THAN':
							$andFilter[] = [$negate . '>' . $subFilter['fieldName'] => $subFilter['values']];
							break;
						case 'NUMERIC_GREATER_THAN_OR_EQUAL':
							$andFilter[] = [$negate . '>=' . $subFilter['fieldName'] => $subFilter['values']];
							break;
						case 'NUMERIC_LESS_THAN':
							$andFilter[] = [$negate . '<' . $subFilter['fieldName'] => $subFilter['values']];
							break;
						case 'NUMERIC_LESS_THAN_OR_EQUAL':
							$andFilter[] = [$negate . '<=' . $subFilter['fieldName'] => $subFilter['values']];
							break;
						default:
							$canBeFiltered = false;
					}
				}
			}
			if (count($andFilter) > 1)
			{
				$sqlWhere[] = $andFilter;
			}
		}

		return $sqlWhere;
	}

	/**
	 * @param array $parameters
	 *
	 * @return \Generator
	 */
	abstract public function query(
		array $parameters,
		int $limit,
		array $dateFormats = []
	): \Generator;

	/**
	 *
	 * @return void
	 */
	public function sendAnalytic(): void
	{
		$event = new AnalyticsEvent('data_request', 'BI_Builder', 'Connector');
		$datasetName = $this->reformatSnakeToCamelCase($this->name);
		$event
			->setSection('BI_Connector')
			->setP1($datasetName)
			->setP2(static::ANALYTIC_TAG_DATASET)
			->send()
		;

	}

	private function reformatSnakeToCamelCase(string $text): string
	{
		$parts = explode('_', $text);

		$camelCase = array_shift($parts);
		$camelCase .= implode('', array_map('ucfirst', $parts));

		return $camelCase;
	}
}

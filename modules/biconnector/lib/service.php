<?php
namespace Bitrix\BIConnector;

abstract class Service
{
	protected $manager = null;
	protected static $serviceId = '';
	public static $dateFormats = [];
	protected $languageMap = null;
	protected $languageId = 'en';

	/**
	 * Creates new service instance.
	 *
	 * @param \Bitrix\BIConnector\Manager $manager Manager instance.
	 */
	public function __construct(\Bitrix\BIConnector\Manager $manager)
	{
		$this->manager = $manager;
	}

	/**
	 * Event OnBIConnectorCreateServiceInstance habler.
	 *
	 * @param \Bitrix\Main\Event $event Event parameters.
	 *
	 * @return \Bitrix\Main\EventResult
	 */
	public static function createServiceInstance(\Bitrix\Main\Event $event)
	{
		$serviceId = $event->getParameters()[0];
		$manager = $event->getParameters()[1];
		$service = null;
		if (static::$serviceId && static::$serviceId === $serviceId)
		{
			$service = new static($manager);
		}
		$result = new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, $service);

		return $result;
	}

	/**
	 * Changes the language if it is exists and active.
	 * Otherwise sets the language to the current one.
	 *
	 * @param string $languageId Interface language identifier.
	 *
	 * @return void
	 * @see \Bitrix\Main\Localization\Loc::getCurrentLang
	 */
	public function setLanguage($languageId)
	{
		$this->languageId = \Bitrix\Main\Localization\Loc::getCurrentLang();
		$dbLanguage = \Bitrix\Main\Localization\LanguageTable::getList([
			'select' => ['LID'],
			'filter' => [
				'=LID' => $languageId,
				'=ACTIVE' => 'Y'
			],
		])->fetch();
		if ($dbLanguage)
		{
			$this->languageId = $dbLanguage['LID'];
		}
	}

	/**
	 * Returns current service language.
	 *
	 * @return string
	 */
	public function getLanguage()
	{
		return $this->languageId;
	}

	/**
	 * Event OnBIConnectorValidateDashboardUrl handler.
	 *
	 * @param  mixed $event Event parameters.
	 *
	 * @return \Bitrix\Main\EventResult
	 */
	public static function validateDashboardUrl(\Bitrix\Main\Event $event)
	{
		$result = new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, 0);

		return $result;
	}

	/**
	 * Retruns array of available data sources.
	 * This will be returned as a json on "show_tables" service command.
	 *
	 * @return array
	 */
	public function getTableList()
	{
		$result = [];
		foreach ($this->manager->getDataSources($this->languageId) as $tableName => $tableInfo)
		{
			$result[] = [
				$tableName,
				$tableInfo['TABLE_DESCRIPTION'],
			];
		}
		return $result;
	}

	/**
	 * Returns internal type external representation.
	 *
	 * @param string $internalType Internal (CSQLWhere) type.
	 * @return string
	 * @see \CSQLWhere
	 */
	protected function mapType($internalType)
	{
		switch ($internalType)
		{
			case 'file':
			case 'enum':
			case 'int':
			case 'double':
				return 'NUMBER';
			case 'string':
			case 'callback':
				return 'STRING';
			case 'date':
				return 'YEAR_MONTH_DAY';
			case 'datetime':
				return 'YEAR_MONTH_DAY_SECOND';
			case 'bool':
				return 'BOOLEAN';
		}
		return 'STRING';
	}

	/**
	 * Retruns array of available data sources.
	 * This will be returned as a json on "desc" service command.
	 *
	 * @param string $tableName Data source name.
	 * @return array
	 */
	public function getTableFields($tableName)
	{
		$result = [];
		$tableInfo = $this->manager->getTableDescription($tableName, $this->languageId);
		if ($tableInfo)
		{
			foreach ($tableInfo['FIELDS'] as $fieldName => $fieldInfo)
			{
				if (isset($fieldInfo['FIELD_TYPE_EX']))
				{
					$type = $fieldInfo['FIELD_TYPE_EX'];
				}
				else
				{
					$type = $fieldInfo['FIELD_TYPE'];
				}

				$result[] = [
					'CONCEPT_TYPE' => (isset($fieldInfo['IS_METRIC']) && $fieldInfo['IS_METRIC'] === 'Y' ? 'METRIC' : 'DIMENSION'),
					'ID' => $fieldName,
					'NAME' => $fieldInfo['FIELD_DESCRIPTION'],
					'DESCRIPTION' => $fieldInfo['FIELD_DESCRIPTION_FULL'] ?? '',
					'TYPE' => $this->mapType($type),
					'AGGREGATION_TYPE' => $fieldInfo['AGGREGATION_TYPE'] ?? null,
					'IS_PRIMARY' => $fieldInfo['IS_PRIMARY'] ?? null,
					'CONCAT_GROUP_BY' => $fieldInfo['CONCAT_GROUP_BY'] ?? null,
					'CONCAT_KEY' => $fieldInfo['CONCAT_KEY'] ?? null,
				];
			}
		}
		return $result;
	}

	/**
	 * applyDateFilter
	 *
	 * @param array &$sqlWhere Modified where.
	 * @param array $tableFields Table metadata.
	 * @param array $dateRange Filters from input.
	 * @param string $timeFilterColumn Column from input.
	 *
	 * @return void
	 */
	protected function applyDateFilter(&$sqlWhere, $tableFields, $dateRange, $timeFilterColumn = '')
	{
		$startDate = array_key_exists('startDate', $dateRange) ? MakeTimeStamp($dateRange['startDate'], 'YYYY-MM-DD') : false;

		$filterColumnName = false;
		if (
			$timeFilterColumn
			&& array_key_exists($timeFilterColumn, $tableFields)
			&& $tableFields[$timeFilterColumn]['FIELD_TYPE'] === 'datetime'
		)
		{
			$filterColumnName = $timeFilterColumn;
		}
		else
		{
			foreach ($tableFields as $fieldName => $fieldInfo)
			{
				if ($fieldInfo['FIELD_TYPE'] === 'datetime')
				{
					$filterColumnName = $fieldName;
					break;
				}
			}
		}

		if ($filterColumnName)
		{
			if ($startDate)
			{
				$sqlWhere['>=' . $filterColumnName] = ConvertTimeStamp($startDate, 'SHORT');
			}

			$endDate = array_key_exists('endDate', $dateRange) ? MakeTimeStamp($dateRange['endDate'], 'YYYY-MM-DD') : false;
			if ($endDate)
			{
				$endDate += 23 * 3600 + 59 * 60 + 59;
				$sqlWhere['<=' . $filterColumnName] = ConvertTimeStamp($endDate, 'FULL');
			}
		}
	}

	/**
	 * applyDimensionsFilters
	 *
	 * @param array &$sqlWhere Modified where.
	 * @param bool &$canBeFiltered Return flag.
	 * @param array $tableFields Table metadata.
	 * @param array $dimensionsFilters Filters from input.
	 *
	 * @return void
	 */
	protected function applyDimensionsFilters(&$sqlWhere, &$canBeFiltered, $tableFields, $dimensionsFilters)
	{
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
							$andFilter[$negate . '=' . $subFilter['fieldName']] = $subFilter['values'];
							break;
						case 'CONTAINS':
							$andFilter[$negate . '%' . $subFilter['fieldName']] = $subFilter['values'];
							break;
						case 'REGEXP_PARTIAL_MATCH':
						case 'REGEXP_EXACT_MATCH':
							$canBeFiltered = false;
							break;
						case 'IS_NULL':
							$andFilter[$negate . '=' . $subFilter['fieldName']] = false;
							break;
						case 'BETWEEN':
							$andFilter[$negate . '><' . $subFilter['fieldName']] = $subFilter['values'];
							break;
						case 'NUMERIC_GREATER_THAN':
							$andFilter[$negate . '>' . $subFilter['fieldName']] = $subFilter['values'];
							break;
						case 'NUMERIC_GREATER_THAN_OR_EQUAL':
							$andFilter[$negate . '>=' . $subFilter['fieldName']] = $subFilter['values'];
							break;
						case 'NUMERIC_LESS_THAN':
							$andFilter[$negate . '<' . $subFilter['fieldName']] = $subFilter['values'];
							break;
						case 'NUMERIC_LESS_THAN_OR_EQUAL':
							$andFilter[$negate . '<=' . $subFilter['fieldName']] = $subFilter['values'];
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
	}

	/**
	 * Retruns array of data source description including metadata and sql.
	 * This will be used to process "data" and "explain" service commands.
	 *
	 * @param string $tableName Data source name.
	 * @param string $parameters Input parameters.
	 *
	 * @return array
	 */
	public function getData($tableName, $parameters)
	{
		$tableInfo = $this->manager->getTableDescription($tableName, $this->languageId);
		$tableFields = $tableInfo['FIELDS'];

		$canBeFiltered = true;
		$sqlWhere = $tableInfo['FILTER'] ?? [];

		if (isset($parameters['dateRange']) && is_array($parameters['dateRange']))
		{
			$timeFilterColumn = $parameters['configParams']['timeFilterColumn'] ?? '';
			$this->applyDateFilter($sqlWhere, $tableFields, $parameters['dateRange'], $timeFilterColumn);
		}

		// https://developers.google.com/datastudio/connector/filters?hl=ru
		if (isset($parameters['dimensionsFilters']) && is_array($parameters['dimensionsFilters']))
		{
			$this->applyDimensionsFilters($sqlWhere, $canBeFiltered, $tableFields, $parameters['dimensionsFilters']);
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
		$concatFields = [];
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
						if (isset($tableField['CONCAT_KEY']))
						{
							$concatKey = $tableField['CONCAT_KEY'];
							$concatFields[$concatKey] = $tableFields[$concatKey];
							$concatFields[$concatKey]['ID'] = $concatKey;
						}
						$selectedFields[$fieldName] = $tableField;
					}
				}
				else
				{
					//TODO
				}
			}
			if (!$selectedFields)
			{
				return [
					'error' => 'EMPTY_SELECT_FIELDS_LIST',
				];
			}
		}
		else
		{
			$selectedFields = $tableFields;
		}

		$primaryFields = [];
		if ($concatFields)
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

		foreach ($concatFields as $concatKey => $_)
		{
			if (isset($selectedFields[$concatKey]))
			{
				unset($concatFields[$concatKey]);
			}
		}

		foreach ($primaryFields as $primaryKey => $_)
		{
			if (isset($selectedFields[$primaryKey]))
			{
				unset($primaryFields[$primaryKey]);
			}
		}

		$shadowFields = array_merge($primaryFields, $concatFields);

		$queryWhere->SetSelect(array_merge($selectedFields, $shadowFields), static::$dateFormats);

		$additionalJoins = $queryWhere->GetJoins();
		$sql = "select\n  " . $queryWhere->GetSelect()
			. "\nfrom\n  " . $tableInfo['TABLE_NAME'] . ' AS ' . $tableInfo['TABLE_ALIAS']
			. ($additionalJoins ? "\n  " . $additionalJoins : '')
			. ($strQueryWhere ? "\nWHERE " . $strQueryWhere : '')
			. "\n";

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
				$fetchCallbacks[$i] = function ($value, $dateFormats)
				{
					return $value === null ? null : (int)$value;
				};
			}
			$i++;
		}

		$schemaFields = $this->getTableFields($tableName);
		$schema = [];
		foreach ($selectedFields as $fieldName => $_)
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
					return [
						'error' => 'DICTIONARY_' . $dictionaryId . '_UPDATE',
					];
				}
			}
		}

		return [
			'schema' => $schema,
			'sql' => $sql,
			'filtersApplied' => $strQueryWhere && $canBeFiltered,
			'onAfterFetch' => $fetchCallbacks,
			'where' => $sqlWhere,
			'shadowFields' => $shadowFields,
		];
	}
}

<?php

namespace Bitrix\BIConnector\DataSourceConnector\Connector;

use Bitrix\Main\Config\ConfigurationException;
use Bitrix\Main\Result;
use Bitrix\BIConnector\Manager;
use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\BIConnector\Aggregate;

class Sql extends Base
{
	protected const ANALYTIC_TAG_DATASET = 'system';

	/**
	 * @param array $parameters
	 * @param int $limit
	 * @param array $dateFormats
	 *
	 * @return \Generator
	 *
	 * @throws ConfigurationException
	 */
	public function query(
		array $parameters,
		int $limit,
		array $dateFormats = []
	): \Generator
	{
		$result = new Result();
		$manager = Manager::getInstance();
		$data = $this->getFormattedData($parameters, $dateFormats);

		$connection = $manager->getDatabaseConnection();
		$resultQuery = $connection->biQuery($data['sql']);
		if (!$resultQuery)
		{
			$result->addError(new Error('SQL_ERROR', 0, ['description' => $connection->getErrorMessage()]));

			return $result;
		}

		$schema = $data['schema'] ?? [];
		$shadowFields = $data['shadowFields'] ?? [];
		$extraCount = count($shadowFields);
		$selectFields = array_merge(array_values($schema) , array_values($shadowFields));

		$primary = [];
		foreach ($selectFields as $i => $fieldInfo)
		{
			if (isset($fieldInfo['IS_PRIMARY']) && $fieldInfo['IS_PRIMARY'] === 'Y')
			{
				$primary[] = $i;
			}
		}

		$groupFields = [];
		foreach ($selectFields as $i => $fieldInfo)
		{
			if (isset($fieldInfo['GROUP_CONCAT']))
			{
				foreach ($selectFields as $j => $keyInfo)
				{
					if ($keyInfo['ID'] === $fieldInfo['GROUP_KEY'])
					{
						if ($fieldInfo['TYPE'] === 'ARRAY_STRING')
						{
							$groupFields[$i] = [
								'unique_id' => $j,
								'state' => new Aggregate\ArrayStringState($fieldInfo['GROUP_CONCAT']),
							];
						}
						else
						{
							$groupFields[$i] = [
								'unique_id' => $j,
								'state' => new Aggregate\ConcatState($fieldInfo['GROUP_CONCAT']),
							];
						}

						break;
					}
				}
			}
			elseif (isset($fieldInfo['GROUP_COUNT']))
			{
				foreach ($selectFields as $j => $keyInfo)
				{
					if ($keyInfo['ID'] === $fieldInfo['GROUP_KEY'])
					{
						$groupFields[$i] = [
							'unique_id' => $j,
							'state' => new Aggregate\CountState($fieldInfo['GROUP_COUNT'] === 'DISTINCT'),
						];

						break;
					}
				}
			}
		}

		$prevPrimaryKey = '';
		$outputRow = null;
		$rowsCount = 0;

		while ($row = $resultQuery->fetch())
		{
			if ($limit && $rowsCount === $limit)
			{
				/** It`s total prohibited for changing `continue` to `break` for avoiding `Commands out of sync`. The issue is 0205035 */
				continue;
			}

			$onAfterCallbacks = $data['onAfterFetch'] ?? [];
			foreach ($onAfterCallbacks as $i => $callback)
			{
				$row[$i] = $callback($row[$i], $dateFormats);
			}

			$primaryKey = '';
			foreach ($primary as $primaryIndex)
			{
				if ($primaryKey)
				{
					$primaryKey .= '-';
				}
				$primaryKey .= $row[$primaryIndex];
			}

			if ($primary && $groupFields)
			{
				if ($outputRow === null)
				{
					$outputRow = $row;
					foreach ($groupFields as $i => $groupInfo)
					{
						$groupId = $row[$groupInfo['unique_id']];
						$outputRow[$i] = clone $groupInfo['state'];
						$outputRow[$i]->updateState($groupId, $row[$i]);
					}
				}
				elseif ($primaryKey === $prevPrimaryKey)
				{
					foreach ($groupFields as $i => $groupInfo)
					{
						$groupId = $row[$groupInfo['unique_id']];
						$outputRow[$i]->updateState($groupId, $row[$i]);
					}
				}
				else
				{
					foreach ($groupFields as $i => $groupInfo)
					{
						$outputRow[$i] = $outputRow[$i]->output();
					}
					if ($extraCount)
					{
						array_splice($outputRow, -$extraCount);
					}

					yield $outputRow;
					$rowsCount++;

					$outputRow = $row;
					foreach ($groupFields as $i => $groupInfo)
					{
						$groupId = $row[$groupInfo['unique_id']];
						$outputRow[$i] = clone $groupInfo['state'];
						$outputRow[$i]->updateState($groupId, $row[$i]);
					}
				}
				$prevPrimaryKey = $primaryKey;
			}
			else
			{
				if ($extraCount)
				{
					array_splice($row, -$extraCount);
				}

				yield $row;
				$rowsCount++;
			}
		}

		if ($outputRow && !($limit && $rowsCount === $limit))
		{
			foreach ($groupFields as $i => $groupInfo)
			{
				$outputRow[$i] = $outputRow[$i]->output();
			}

			if ($extraCount)
			{
				array_splice($outputRow, -$extraCount);
			}

			yield $outputRow;
		}

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
		$additionalJoins = $builder->GetJoins();

		$queryWhere = null;
		$whereJoints = null;
		if ($where)
		{
			$queryWhere = $builder->GetQuery($where);
			$whereJoints = $builder->GetJoins();
		}

		$limit = null;
		$limitSubQuery = null;
		if (
			isset($queryParameters['limit'])
			&& $queryParameters['limit'] > 0
			&& is_array($tableInfo['FIELDS'])
		)
		{
			$primaryKey = null;
			foreach ($tableInfo['FIELDS'] as $id => $fieldInfo)
			{
				if ($fieldInfo['IS_PRIMARY'] === 'Y')
				{
					$primaryKey = $fieldInfo['FIELD_NAME'];
					$helper = Application::getConnection()->getSqlHelper();
					$primaryKey = $helper->forSql($primaryKey);

					break;
				}
			}

			$limit = "limit " . (int)$queryParameters['limit'];
			if (!empty($primaryKey))
			{
				$limit = " order by {$primaryKey} asc \n {$limit}";
			}

			if (!empty($additionalJoins))
			{
				if (!empty($primaryKey) && !empty($fieldInfo['FIELD_NAME']))
				{
					$subSelect = "	
						select $primaryKey as ID
						from
						{$tableInfo['TABLE_NAME']} AS {$tableInfo['TABLE_ALIAS']}
					";
					if ($queryWhere)
					{
						$subSelect .= $whereJoints ? "\n {$whereJoints}" : '';
						$subSelect .= $queryWhere ? "\n WHERE {$queryWhere}" : '';
					}

					$subSelect .= "\n {$limit} \n";
					$limitSubQuery = "INNER JOIN ({$subSelect}) as SUBQRYLIMIT on SUBQRYLIMIT.ID = {$primaryKey}";
				}
			}
		}

		return
			"select\n  " . $builder->GetSelect()
			. "\nfrom\n  " . $tableInfo['TABLE_NAME'] . ' AS ' . $tableInfo['TABLE_ALIAS']
			. ($limitSubQuery ? "\n  {$limitSubQuery}" : '')
			. ($additionalJoins ? "\n  " . $additionalJoins : '')
			. ($queryWhere ? "\nWHERE " . $queryWhere : '')
			. (!empty($tableInfo['UNIONS']) ? "\n" . $this->getUnionQuery($builder, $tableInfo['UNIONS']) : '')
			. (empty($limitSubQuery) && !empty($limit) ? "\n {$limit}" : '')
			. "\n"
		;
	}

	private function getUnionQuery(\CBIConnectorSqlBuilder $builder, array $unions): string
	{
		$unionQuery = '';
		$selectFieldNames = $builder->GetSelectFieldNames();
		foreach ($unions as $union)
		{
			$formattedFields = [];
			foreach ($union['FIELDS'] as $unionFieldKey => $unionField)
			{
				if (in_array($unionFieldKey, $selectFieldNames, true))
				{
					$formattedFields[] = $unionField['FIELD_NAME'] . ' AS ' . $unionFieldKey;
				}
			}
			$sqlFields = implode("\n  ,", $formattedFields);
			$unionQuery .= "UNION SELECT\n  " . $sqlFields
				. "\nfrom\n  " . $union['TABLE_NAME'] . ' AS ' . $union['TABLE_ALIAS']
				. "\n"
			;
		}

		return $unionQuery;
	}
}

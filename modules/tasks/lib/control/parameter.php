<?php

namespace Bitrix\Tasks\Control;

use Bitrix\Main\Application;
use Bitrix\Tasks\Internals\Task\ParameterTable;

class Parameter
{
	use BaseControlTrait;

	private const DEFAULT_VALUE = 'N';

	/**
	 * @param array $data
	 * @return void
	 */
	public function add(array $data)
	{
		if (
			!array_key_exists('SE_PARAMETER', $data)
			|| !is_array($data['SE_PARAMETER'])
			|| empty($data['SE_PARAMETER'])
		)
		{
			$data['SE_PARAMETER'] = [];
		}

		$rows = $this->filterValues($data['SE_PARAMETER']);
		$rows = $this->addDefaultValues($rows);

		$this->set($rows);
	}

	/**
	 * @param array $data
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function update(array $data)
	{
		if (
			!array_key_exists('SE_PARAMETER', $data)
			|| !is_array($data['SE_PARAMETER'])
		)
		{
			return;
		}

		$rows = $this->filterValues($data['SE_PARAMETER']);
		$rows = array_combine(array_column($rows, 'CODE'), $rows);

		$taskParams = $this->getTaskParameters();
		$taskParams = $this->addDefaultValues($taskParams);

		foreach ($taskParams as $k => $param)
		{
			if (array_key_exists($param['CODE'], $rows))
			{
				$taskParams[$k]['VALUE'] = $rows[$param['CODE']]['VALUE'];
			}
		}

		$this->set($taskParams);
	}

	/**
	 * @param array $rows
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function set(array $rows)
	{
		ParameterTable::deleteList([
			'=TASK_ID' => $this->taskId,
		]);

		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$insertRows = [];
		foreach($rows as $row)
		{
			$insertRows[] = implode(',', [
				$this->taskId,
				(int) $row['CODE'],
				"'" . $sqlHelper->forSql($row['VALUE']) . "'"
			]);
		}

		$sql = "
			INSERT INTO ". ParameterTable::getTableName() ."
			(`TASK_ID`, `CODE`, `VALUE`)
			VALUES
			(". implode("),(", $insertRows) .")
		";

		Application::getConnection()->query($sql);
	}

	/**
	 * @param array $params
	 * @return array
	 */
	private function filterValues(array $params): array
	{
		$codes = ParameterTable::paramsList();

		foreach ($params as $k => $row)
		{
			if (
				!isset($row['CODE'])
				|| !in_array($row['CODE'], $codes)
			)
			{
				unset($params[$k]);
			}

			if (
				!isset($row['VALUE'])
				|| !in_array($row['VALUE'], ['N', 'Y'])
			)
			{
				$params[$k]['VALUE'] = self::DEFAULT_VALUE;
			}
		}

		return $params;
	}

	/**
	 * @param array $params
	 * @return array
	 */
	private function addDefaultValues(array $params): array
	{
		$codes = ParameterTable::paramsList();

		$existsCodes = array_column($params, 'CODE');
		$diffCodes = array_diff($codes, $existsCodes);

		foreach ($diffCodes as $code)
		{
			$params[] = [
				'CODE' => $code,
				'VALUE' => self::DEFAULT_VALUE,
			];
		}

		return $params;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getTaskParameters(): array
	{
		return ParameterTable::getList([
			'select' => ['*'],
			'filter' => [
				'=TASK_ID' => $this->taskId,
			]
		])->fetchAll();
	}
}
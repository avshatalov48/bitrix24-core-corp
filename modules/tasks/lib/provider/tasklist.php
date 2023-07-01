<?php

namespace Bitrix\Tasks\Provider;

use Bitrix\Main\ModuleManager;
use Bitrix\Main\ORM\Query\Result;

class TaskList
{
	/**
	 * @var TaskQuery
	 */
	private $query;

	/**
	 * @var \CDBResult
	 */
	private $dbResult;

	public function __construct()
	{

	}

	/**
	 * @param TaskQuery $query
	 * @return array
	 * @throws Exception\InvalidSelectException
	 * @throws Exception\UnexpectedTableException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getList(TaskQuery $query): array
	{
		$this->query = clone $query;
		$this->prepareQuery();

		// if ($query->needSeparated())
		// {
		// 	$taskIds = $this->getTaskIds();
		// 	// set filter by task ids
		// 	$this->query->setWhere([
		// 		'ID' => $taskIds,
		// 	]);
		// 	$this->query->skipAccessCheck();
		// }

		$dbQuery = TaskQueryBuilder::build($this->query);
		$this->dbResult = $dbQuery->exec();

		$tasks = $this->dbResult->fetchAll();
		$tasks = $this->loadRelations($tasks);
		$tasks = $this->prepareResult($tasks);

		return $tasks;
	}

	/**
	 * @param TaskQuery $query
	 * @return int
	 * @throws Exception\InvalidSelectException
	 * @throws Exception\UnexpectedTableException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getCount(TaskQuery $query): int
	{
		$this->query = clone $query;

		$this->query
			->addSelect(['COUNT'])
			->setOrder([])
			->setLimit(0);

		$dbQuery = TaskQueryBuilder::build($this->query);
		$result = $dbQuery->fetch();

		return isset($result['COUNT']) ? (int) $result['COUNT'] : 0;
	}

	/**
	 * @return \CDBResult|null
	 */
	public function getLastDbResult(): ?Result
	{
		return $this->dbResult;
	}

	/**
	 * @return void
	 */
	private function prepareQuery(): void
	{
		$select = $this->query->getSelect();

		if (
			in_array('DESCRIPTION', $select)
			&& !in_array('DESCRIPTION_IN_BBCODE', $select)
		)
		{
			$select[] = 'DESCRIPTION_IN_BBCODE';
		}

		if (!ModuleManager::isModuleInstalled('forum'))
		{
			$select = array_diff($select, ['COMMENTS_COUNT', 'FORUM_ID', 'SERVICE_COMMENTS_COUNT']);
		}

		$this->query->setSelect($select);
	}

	/**
	 * @param array $tasks
	 * @return array
	 */
	private function prepareResult(array $tasks): array
	{
		$translateMap = TaskQueryBuilder::getTranslateMap();

		foreach ($tasks as $k => $row)
		{
			foreach ($translateMap as $source => $destination)
			{
				if (array_key_exists($destination, $row))
				{
					$tasks[$k][$source] = $tasks[$k][$destination];
					unset($tasks[$k][$destination]);
				}
			}
		}

		return $tasks;
	}

	/**
	 * @param array $tasks
	 * @return array
	 */
	private function loadRelations(array $tasks): array
	{
		return $tasks;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getTaskIds(): array
	{
		$query = clone $this->query;
		$query->setSelect('ID');

		$ids = TaskQueryBuilder::build($query)
			->fetchAll();

		$ids = array_column($ids, 'ID');

		return $ids;
	}

}
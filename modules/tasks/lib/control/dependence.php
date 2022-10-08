<?php

namespace Bitrix\Tasks\Control;

use Bitrix\Main\Application;
use Bitrix\Tasks\Internals\Task\RelatedTable;

class Dependence
{
	use BaseControlTrait;

	/**
	 * @param array $depends
	 * @return void
	 * @throws Exception\TaskNotFoundException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function setPrevious($depends = [])
	{
		$this->loadTask();

		$this->deleteByTask();

		if (
			!is_array($depends)
			|| empty($depends)
		)
		{
			return;
		}

		$depends = array_map(function($el) {
			return (int) $el;
		}, $depends);
		$depends = array_unique($depends);

		$insertRows = [];
		foreach ($depends as $dependId)
		{
			$insertRows[] = [
				'TASK_ID' => $this->taskId,
				'DEPENDS_ON_ID' => $dependId,
			];
		}

		$insertRows = array_map(function($el) {
			return implode(',', $el);
		}, $insertRows);

		$sql = "
			INSERT INTO ". RelatedTable::getTableName() ."
			(`TASK_ID`, `DEPENDS_ON_ID`)
			VALUES
			(". implode("),(", $insertRows) .")
		";

		Application::getConnection()->query($sql);
	}

	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function deleteByTask()
	{
		RelatedTable::deleteList([
			'TASK_ID' => $this->taskId,
		]);
	}
}
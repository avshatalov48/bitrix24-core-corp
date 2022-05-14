<?php

namespace Bitrix\Tasks\Control;

use Bitrix\Main\Application;
use Bitrix\Tasks\Control\Exception\TaskNotFoundException;
use Bitrix\Tasks\Internals\Task\TagTable;

class Tag
{
	use BaseControlTrait;

	/**
	 * @param array $tags
	 * @return void
	 * @throws TaskNotFoundException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function set(array $tags)
	{
		$this->deleteByTask();

		if (empty($tags))
		{
			return;
		}

		$insertRows = [];
		foreach ($tags as $tag)
		{
			$insertRows[] = [
				'TASK_ID' => $this->taskId,
				'USER_ID' => $this->userId,
				'NAME' => $tag,
			];
		}

		$insertRows = array_map(function($el) {
			$implode = $el['USER_ID'];
			$implode .= ','.$el['TASK_ID'];
			$implode .= ',\''. Application::getConnection()->getSqlHelper()->forSql(trim($el['NAME'])) .'\'';
			return $implode;
		}, $insertRows);

		$sql = "
			INSERT INTO ". TagTable::getTableName() ."
			(`USER_ID`, `TASK_ID`, `NAME`)
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
	public function deleteByTask()
	{
		TagTable::deleteList([
			'TASK_ID' => $this->taskId,
		]);
	}
}
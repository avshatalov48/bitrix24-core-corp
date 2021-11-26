<?php
namespace Bitrix\Tasks\Update;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Update\Stepper;
use Bitrix\Main\SystemException;
use Bitrix\Forum\MessageTable;
use Bitrix\Tasks\Internals\SearchIndex;
use Bitrix\Tasks\Internals\TaskTable;

/**
 * Class FullTasksIndexer
 *
 * @package Bitrix\Tasks\Update
 */
final class FullTasksIndexer extends Stepper
{
	protected static $moduleId = 'tasks';

	/**
	 * @param array $result
	 * @return bool
	 * @throws ArgumentException
	 * @throws ArgumentNullException
	 * @throws ArgumentOutOfRangeException
	 * @throws SqlQueryException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function execute(array &$result)
	{
		if (!(
			Loader::includeModule("tasks") &&
			Option::get("tasks", "needFullTasksIndexing", "Y") == 'Y'
		))
		{
			return false;
		}

		$return = false;

		$params = Option::get("tasks", "fulltasksindexing", "");
		$params = ($params !== ""? @unserialize($params, ['allowed_classes' => false]) : []);
		$params = (is_array($params)? $params : []);

		if (empty($params))
		{
			$params = static::fillSourceParams();
		}

		$found = false;

		if ($params["count"] > 0)
		{
			$result["progress"] = 1;
			$result["steps"] = "";
			$result["count"] = $params["count"];

			$time = time();

			if ($params["last_task_id"] !== $params["last_task_to_index"])
			{
				$tasksHandlerInfo = static::handleTasks($time, $params);

				$found = $tasksHandlerInfo['FOUND'];
				$params = $tasksHandlerInfo['PARAMS'];
			}
			else if (static::checkForumIncluded())
			{
				$commentsHandlerInfo = static::handleComments($time, $params);

				$found = $commentsHandlerInfo['FOUND'];
				$params = $commentsHandlerInfo['PARAMS'];
			}

			if ($found)
			{
				Option::set("tasks", "fulltasksindexing", serialize($params));
				$return = true;
			}

			$result["progress"] = (int)($params["number"] * 100 / $params["count"]);
			$result["steps"] = $params["number"];
		}

		if ($found === false)
		{
			Option::delete("tasks", ["name" => "fulltasksindexing"]);
			Option::set("tasks", "needFullTasksIndexing", "N");
		}

		return $return;
	}

	/**
	 * @param $time
	 * @param $params
	 * @return array
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private static function handleTasks($time, $params)
	{
		$found = false;

		$tasksRes = TaskTable::getList([
			'select' => ['ID'],
			'filter' => [
				'>ID' => $params["last_task_id"],
				'<ID' => $params["last_task_to_index"] + 1,
			],
			'order' => ['ID' => 'ASC'],
			'offset' => 0,
			'limit' => 100
		]);

		while ($task = $tasksRes->fetch())
		{
			$taskId = $task['ID'];

			SearchIndex::setTaskSearchIndex($taskId, []);

			$params["number"]++;
			$params["last_task_id"] = (int)$taskId;

			$found = true;

			if (time() - $time > 3)
			{
				break;
			}
		}

		return [
			'FOUND' => $found,
			'PARAMS' => $params
		];
	}

	/**
	 * @param $time
	 * @param $params
	 * @return array
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws LoaderException
	 * @throws SystemException
	 */
	private static function handleComments($time, $params)
	{
		$found = false;

		$query = new Query(TaskTable::getEntity());
		$query->setSelect([
			'TASK_ID' => 'ID',
			'MESSAGE_ID' => 'FM.ID',
			'POST_MESSAGE' => 'FM.POST_MESSAGE'
		]);
		$query->registerRuntimeField(
			'',
			new ReferenceField(
				'FM',
				MessageTable::class,
				Join::on('this.FORUM_TOPIC_ID', 'ref.TOPIC_ID')
					->where('ref.NEW_TOPIC', 'N'),
				['join_type' => 'INNER']
			)
		);
		$query
			->where('FM.ID', '>', $params["last_comment_id"])
		;
		$query->setOrder(['FM.ID' => 'ASC']);
		$query->setOffset(0);
		$query->setLimit(100);

		$commentsRes = $query->exec();

		while ($comment = $commentsRes->fetch())
		{
			$taskId = $comment['TASK_ID'];
			$commentId = $comment['MESSAGE_ID'];
			$commentText = $comment['POST_MESSAGE'];

			SearchIndex::setCommentSearchIndex($taskId, $commentId, $commentText);

			$params["number"]++;
			$params["last_comment_id"] = $commentId;

			$found = true;

			if (time() - $time > 3)
			{
				break;
			}
		}

		return [
			'FOUND' => $found,
			'PARAMS' => $params
		];
	}

	/**
	 * @return array
	 * @throws ArgumentException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function fillSourceParams(): array
	{
		$tasksRes = TaskTable::getList([
			'select' => ['ID'],
			'order' => ['ID' => 'ASC'],
			'count_total' => true,
		]);
		$tasks = $tasksRes->fetchAll();
		$count = $tasksRes->getCount();

		reset($tasks);
		$lastTask = end($tasks);
		$lastTaskToIndex = (int)$lastTask['ID'];

		if (static::checkForumIncluded())
		{
			$query = new Query(TaskTable::getEntity());
			$query->setSelect([new ExpressionField('CNT', 'COUNT(*)')]);
			$query->registerRuntimeField(
				'',
				new ReferenceField(
					'FM',
					MessageTable::class,
					Join::on('this.FORUM_TOPIC_ID', 'ref.TOPIC_ID')
						->where('ref.NEW_TOPIC', 'N'),
					['join_type' => 'INNER']
				)
			);

			$commentsCountRes = $query->exec();
			if ($commentsCount = $commentsCountRes->fetch())
			{
				$count += (int)$commentsCount['CNT'];
			}
		}

		return [
			'number' => 0,
			'last_task_id' => 0,
			'last_task_to_index' => $lastTaskToIndex,
			'last_comment_id' => 0,
			'count' => $count,
		];
	}

	/**
	 * @return bool
	 * @throws LoaderException
	 */
	private static function checkForumIncluded()
	{
		return Loader::includeModule("forum");
	}
}
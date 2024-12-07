<?php
/**
 * Class implements all further interactions with "forum" module considering "task comment" entity
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration\Forum\Task;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\AttachedObjectTable;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Item\Context;
use CTaskAssert;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\TaskAccessController;

final class Topic extends \Bitrix\Tasks\Integration\Forum
{
	public const TYPE_TASK = 'TK';
	
	/**
	 * Fires when new topic was added for the task
	 *
	 * @param $entityType
	 * @param $entityId
	 * @param $arPost
	 * @param $arTopic
	 * @return bool|void
	 */
	public static function onBeforeAdd($entityType, $entityId, $arPost, &$arTopic)
	{
		if ($entityType !== self::TYPE_TASK)
		{
			return;
		}

		$taskId = (int)$entityId;
		if($taskId <= 0)
		{
			CTaskAssert::logError('[0xb6324222] Expected integer $entityId >= 1');
			return;
		}

		$task = TaskRegistry::getInstance()->getObject($taskId);
		if (is_null($task))
		{
			return true; // <-- disgusting. compatability...
		}

		$userId = Context::getDefault()->getUserId(); // <-- disgusting. compatability...

		if(TaskAccessController::can($userId, ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			$arTopic["TITLE"] = $task->getTitle();
			$arTopic["MESSAGE"] = trim($task->getTitle() . "\n" . $task->getDescription());
			$arTopic["AUTHOR_ID"] = $task->getCreatedBy();
		}

		return true;
	}

	public static function onAfterAdd($entityType, $entityId, $topicId)
	{
		if ($entityType !== self::TYPE_TASK)
		{
			return;
		}

		$taskId = (int)$entityId;
		if($taskId <= 0)
		{
			CTaskAssert::logError('[0xb6324222] Expected integer $entityId >= 1');
			return;
		}

		TaskRegistry::getInstance()
			->getObject($taskId)
			?->setForumTopicId($topicId)
			?->save();

		return true;
	}

	/**
	 * Updates forum topic title to match task's title
	 *
	 * @param $topicId
	 * @param $title
	 * @return bool
	 */
	public static function updateTopicTitle($topicId, $title)
	{
		if (!Loader::includeModule('forum'))
		{
			return false;
		}

		$forumTopic = \CForumTopic::GetByID($topicId);

		if ($forumTopic)
		{
			$fields = array(
				'TITLE' => $title,
				'TITLE_SEO' => \CUtil::translit(
					$title,
					LANGUAGE_ID,
					array("max_len" => 255, "safe_chars" => ".", "replace_space" => '-')
				)
			);

			\CForumTopic::Update($topicId, $fields);
		}

		return true;
	}

	/**
	 * Get file count for a topic
	 *
	 * @param int $taskId
	 * @return int
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getFileCount(int $taskId)
	{
		$count = 0;

		$task = TaskRegistry::getInstance()->getObject($taskId);
		if (!$task)
		{
			return 0;
		}

		$topicId = $task->getForumTopicId();
		$forumId = Comment::getForumId();

		if($forumId && $topicId && static::includeModule() && Loader::includeModule("disk"))
		{
			$userFieldManager = Driver::getInstance()->getUserFieldManager();
			[$connectorClass, $moduleId] = $userFieldManager->getConnectorDataByEntityType("forum_message");

			$countQuery = new Query(AttachedObjectTable::getEntity());
			$totalCnt = $countQuery
				->setFilter(array(
					"=ENTITY_TYPE" => $connectorClass,
					"=MODULE_ID" => $moduleId,
					"=VERSION_ID" => null,
				))
				->addSelect(new ExpressionField("CNT", "COUNT(1)"))
				->registerRuntimeField("",
					new ReferenceField(
						"M",
						"Bitrix\\Forum\\MessageTable",
						array(
							"=this.ENTITY_ID" => "ref.ID",
							"=ref.TOPIC_ID" => new SqlExpression("?i", $topicId),
							"=ref.FORUM_ID" => new SqlExpression("?i", $forumId),
						),
						array(
							"join_type" => "INNER"
						)
					)
				)
				->setLimit(null)
				->setOffset(null)
				->exec()
				->fetch();

			$count = intval($totalCnt["CNT"]);
		}

		return $count;
	}

	public static function delete($id)
	{
		$id = intval($id);

		if($id && static::includeModule())
		{
			\CForumTopic::Delete($id);
		}
	}
}
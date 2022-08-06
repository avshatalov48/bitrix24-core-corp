<?php

namespace Bitrix\Tasks\Rest\Controllers\Task;

use Bitrix\Main\Error;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Internals\Task\Result\ResultManager;
use Bitrix\Tasks\Internals\Task\Result\ResultTable;
use Bitrix\Tasks\Rest\Controllers\Base;
use Bitrix\Tasks\Access\Model\UserModel;
use Bitrix\Tasks\Access\ActionDictionary;

class Result extends Base
{
	/**
	 * @param int $commentId
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function addFromCommentAction(int $commentId)
	{
		if (!$commentId)
		{
			$this->errorCollection->add([new Error('Comment not found.')]);
			return null;
		}

		$userId = $this->getUserId();
		if (!$userId)
		{
			$this->errorCollection->add([new Error('Access denied.')]);
			return null;
		}

		if (
			!\Bitrix\Main\Loader::includeModule('tasks')
			|| !\Bitrix\Main\Loader::includeModule('forum')
		)
		{
			$this->errorCollection->add([new Error('Module not loaded.')]);
			return null;
		}

		$comment = \Bitrix\Forum\MessageTable::getById($commentId)->fetchObject();
		if (!$comment)
		{
			$this->errorCollection->add([new Error('Comment not found.')]);
			return null;
		}

		$taskId = (int) str_replace('TASK_', '', $comment->getXmlId());

		if (
			(
				!UserModel::createFromId($userId)->isAdmin()
				&& $comment->getAuthorId() !== $userId
			)
			|| !TaskAccessController::can($userId, ActionDictionary::ACTION_TASK_READ, $taskId)
		)
		{
			$this->errorCollection->add([new Error('Access denied.')]);
			return null;
		}

		$isExists = ResultTable::GetList([
			'filter' => [
				'=COMMENT_ID' => $commentId,
				'=STATUS' => ResultTable::STATUS_OPENED,
			],
			'limit' => 1,
		])->fetchObject();

		if ($isExists)
		{
			$this->errorCollection->add([new Error('Result already exists.')]);
			return null;
		}

		$result = (new ResultManager($userId))->createFromComment($commentId, false);
		return $result->toArray();
	}

	/**
	 * @param int $commentId
	 * @return null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function deleteFromCommentAction(int $commentId)
	{
		if (!$commentId)
		{
			$this->errorCollection->add([new Error('Comment not found.')]);
			return null;
		}

		$userId = $this->getUserId();
		if (!$userId)
		{
			$this->errorCollection->add([new Error('Access denied.')]);
			return null;
		}

		if (
			!\Bitrix\Main\Loader::includeModule('tasks')
			|| !\Bitrix\Main\Loader::includeModule('forum')
		)
		{
			$this->errorCollection->add([new Error('Module not loaded.')]);
			return null;
		}

		$comment = \Bitrix\Forum\MessageTable::getById($commentId)->fetchObject();
		if (!$comment)
		{
			$this->errorCollection->add([new Error('Comment not found.')]);
			return null;
		}

		$taskId = (int) str_replace('TASK_', '', $comment->getXmlId());

		if (
			(
				!UserModel::createFromId($userId)->isAdmin()
				&& $comment->getAuthorId() !== $userId
			)
			|| !TaskAccessController::can($userId, ActionDictionary::ACTION_TASK_READ, $taskId)
		)
		{
			$this->errorCollection->add([new Error('Access denied.')]);
			return null;
		}

		(new ResultManager($userId))->deleteByComment($commentId);
		return null;
	}

	/**
	 * @param int $taskId
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function listAction(int $taskId)
	{
		if (!$taskId)
		{
			$this->errorCollection->add([new Error('Task not found.')]);
			return null;
		}

		$userId = $this->getUserId();
		if (!$userId)
		{
			$this->errorCollection->add([new Error('Access denied.')]);
			return null;
		}

		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			$this->errorCollection->add([new Error('Module not loaded.')]);
			return null;
		}

		if (!TaskAccessController::can($userId, ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			$this->errorCollection->add([new Error('Access denied.')]);
			return null;
		}

		$results = (new ResultManager($userId))->getTaskResults($taskId);

		$res = [];
		foreach ($results as $result)
		{
			$res[] = $result->toArray();
		}

		return $res;
	}
}
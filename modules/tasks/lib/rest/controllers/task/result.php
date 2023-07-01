<?php

namespace Bitrix\Tasks\Rest\Controllers\Task;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Integration\Disk;
use Bitrix\Tasks\Integration\TasksMobile;
use Bitrix\Tasks\Internals\Task\Result\ResultManager;
use Bitrix\Tasks\Internals\Task\Result\ResultTable;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Rest\Controllers\Base;
use Bitrix\Tasks\Access\Model\UserModel;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Ui\Avatar;
use Bitrix\Tasks\Util\User;

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
			!Loader::includeModule('tasks')
			|| !Loader::includeModule('forum')
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
			!Loader::includeModule('tasks')
			|| !Loader::includeModule('forum')
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
	public function listAction(int $taskId, array $params = [])
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

		if (!Loader::includeModule('tasks'))
		{
			$this->errorCollection->add([new Error('Module not loaded.')]);
			return null;
		}

		if (!TaskAccessController::can($userId, ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			$this->errorCollection->add([new Error('Access denied.')]);
			return null;
		}

		$list = [];
		$taskResults = (new ResultManager($userId))->getTaskResults($taskId);
		foreach ($taskResults as $result)
		{
			$data = $result->toArray();
			if (!is_array($data['files']))
			{
				$data['files'] = [];
			}
			$list[] = $data;
		}

		if (empty($list))
		{
			return [];
		}

		$params = [
			'WITH_USER_INFO' => (array_key_exists('WITH_USER_INFO', $params) && $params['WITH_USER_INFO'] === 'Y'),
			'WITH_PARSED_TEXT' => (array_key_exists('WITH_PARSED_TEXT', $params) && $params['WITH_PARSED_TEXT'] === 'Y'),
			'WITH_FILE_INFO' =>
				(array_key_exists('WITH_PARSED_TEXT', $params) && $params['WITH_PARSED_TEXT'] === 'Y')
				|| (array_key_exists('WITH_FILE_INFO', $params) && $params['WITH_FILE_INFO'] === 'Y')
			,
		];
		if ($params['WITH_USER_INFO'])
		{
			$list = $this->fillWithUserInfo($list);
		}
		if ($params['WITH_FILE_INFO'])
		{
			$list = $this->fillWithFileInfo($list);
		}
		if ($params['WITH_PARSED_TEXT'])
		{
			$list = $this->fillWithParsedText($list);
		}

		foreach ($list as $key => $result)
		{
			$list[$key]['fileInfo'] = $this->convertKeysToCamelCase($result['fileInfo']);
		}

		return $list;
	}

	public function getFirstAction(int $taskId): ?array
	{
		if ($taskId <= 0)
		{
			$this->errorCollection->setError(new Error('Task not found.'));
			return null;
		}

		if ($this->getUserId() <= 0)
		{
			$this->errorCollection->setError(new Error('Access denied.'));
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			$this->errorCollection->setError(new Error('Module not loaded.'));
			return null;
		}

		if (!TaskAccessController::can($this->getUserId(), ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			$this->errorCollection->setError(new Error('Access denied.'));
			return null;
		}

		try
		{
			$task = new TaskObject(['ID' => $taskId]);
			$result = $task->getFirstResult();
			if (is_null($result))
			{
				return ['result' => 0];
			}
			$resultId = $result->getId();
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage()));
			return null;
		}

		return ['result' => $resultId];
	}

	public function getLastAction(int $taskId): ?array
	{
		if ($taskId <= 0)
		{
			$this->errorCollection->setError(new Error('Task not found.'));
			return null;
		}

		if ($this->getUserId() <= 0)
		{
			$this->errorCollection->setError(new Error('Access denied.'));
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			$this->errorCollection->setError(new Error('Module not loaded.'));
			return null;
		}

		if (!TaskAccessController::can($this->getUserId(), ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			$this->errorCollection->setError(new Error('Access denied.'));
			return null;
		}

		try
		{
			$task = new TaskObject(['ID' => $taskId]);
			$result = $task->getLastResult();
			if (is_null($result))
			{
				return ['result' => 0];
			}
			$resultId = $result->getId();
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage()));
			return null;
		}

		return ['result' => $resultId];
	}

	private function fillWithUserInfo(array $results): array
	{
		$userIds = array_unique(array_column($results, 'createdBy'));
		$users = User::getData($userIds, ['ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'LOGIN', 'PERSONAL_PHOTO']);

		foreach ($results as $key => $result)
		{
			$createdBy = $result['createdBy'];
			$userData = $users[$createdBy];

			$results[$key]['userInfo'][$createdBy] = [
				'id' => $userData['ID'],
				'name' => $userData['NAME'],
				'secondName' => $userData['SECOND_NAME'],
				'lastName' => $userData['LAST_NAME'],
				'formattedName' => User::formatName($userData),
				'avatar' => Avatar::getPerson($userData['PERSONAL_PHOTO']),
			];
		}

		return $results;
	}

	private function fillWithFileInfo(array $results): array
	{
		$fileIds = array_column($results, 'files');
		$fileIds = array_merge(...$fileIds);
		$fileIds = array_unique($fileIds);

		$attachmentsData = Disk::getAttachmentData($fileIds);

		foreach ($results as $key => $result)
		{
			$results[$key]['fileInfo'] = [];

			foreach ($result['files'] as $fileId)
			{
				if (array_key_exists($fileId, $attachmentsData))
				{
					$results[$key]['fileInfo'][$fileId] = $attachmentsData[$fileId];
				}
			}
		}

		return $results;
	}

	private function fillWithParsedText(array $list): array
	{
		if ($textFragmentParserClass = TasksMobile\TextFragmentParser::getTextFragmentParserClass())
		{
			$textFragmentParser = new $textFragmentParserClass();

			foreach ($list as $key => $result)
			{
				$textFragmentParser->setText($result['text']);
				$textFragmentParser->setFiles($result['fileInfo']);

				$list[$key]['parsedText'] = $textFragmentParser->getParsedText();
			}
		}

		return $list;
	}
}
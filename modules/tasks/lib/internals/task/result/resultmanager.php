<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Task\Result;

use Bitrix\Disk\AttachedObject;
use Bitrix\Forum\MessageTable;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UrlPreview\UrlPreview;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\ParameterTable;
use Bitrix\Tasks\Internals\Task\Result\Exception\ResultNotFoundException;
use Bitrix\Tasks\Internals\Task\Result\Exception\ResultSystemException;
use Bitrix\Tasks\Internals\TaskObject;

class ResultManager
{
	public const COMMENT_SERVICE_DATA = 'TASK_RESULT';

	public const COMMAND_CREATE = 'task_result_create';
	public const COMMAND_DELETE = 'task_result_delete';
	public const COMMAND_UPDATE = 'task_result_update';

	private $userId;
	private $ufManager;

	/**
	 * @param array $taskIds
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function findResultComments(array $taskIds): array
	{
		$taskIds = array_unique($taskIds);

		$res = ResultTable::getList([
			'select' => ['TASK_ID', 'COMMENT_ID', 'STATUS'],
			'filter' => [
				'@TASK_ID' => $taskIds,
			],
		]);

		$commentIds = array_fill_keys($taskIds, []);

		while ($row = $res->fetch())
		{
			$commentIds[$row['TASK_ID']][] = (int)$row['COMMENT_ID'];
		}

		return $commentIds;
	}

	/**
	 * @param int $taskId
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function hasResult(int $taskId): bool
	{
		$res = ResultTable::GetList([
			'select' => ['ID'],
			'filter' => [
				'=TASK_ID' => $taskId,
				'=STATUS' => ResultTable::STATUS_OPENED,
			],
			'limit' => 1,
		])->fetch();

		return $res && (int)$res['ID'] > 0;
	}

	/**
	 * @param int $taskId
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function requireResult(int $taskId): bool
	{
		$res = ParameterTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=TASK_ID' => $taskId,
				'=CODE' => ParameterTable::PARAM_RESULT_REQUIRED,
				'=VALUE' => 'Y',
			],
			'limit' => 1
		])->fetch();

		return $res && (int)$res['ID'] > 0;
	}

	/**
	 * @param int $userId
	 */
	public function __construct(int $userId)
	{
		global $USER_FIELD_MANAGER;
		$this->userId = $userId;
		$this->ufManager = $USER_FIELD_MANAGER;

		$this->includeModules();
	}

	/**
	 * @param int $commentId
	 * @return Result|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function createFromComment(int $commentId, bool $checkServiceData = true): ?Result
	{
		$comment = $this->loadComment($commentId);
		if (
			!$comment
			|| (int) $comment->getServiceType() === 1
		)
		{
			return null;
		}

		if (
			$checkServiceData
			&& $comment->getServiceData() !== self::COMMENT_SERVICE_DATA
		)
		{
			return null;
		}
		else if (
			!$checkServiceData
			&& $comment->getServiceData() !== self::COMMENT_SERVICE_DATA
		)
		{
			$comment->setServiceData(self::COMMENT_SERVICE_DATA);
			$comment->save();
		}

		$taskId = (int) str_replace('TASK_', '', $comment->getXmlId());

		$task = $this->loadTask($taskId);
		if (!$task)
		{
			return null;
		}

		$result = new Result();
		$result->setTaskId($taskId);
		$result->setCommentId($commentId);
		$result->setCreatedBy($this->userId);
		$result->setCreatedAt($comment->getPostDate());
		$result->setText($comment->getPostMessage());
		$result->setUpdatedAt($comment->getPostDate());

		if (in_array($task->getStatus(), [\CTasks::STATE_COMPLETED,\CTasks::STATE_SUPPOSEDLY_COMPLETED]))
		{
			$result->setStatus(ResultTable::STATUS_CLOSED);
		}
		else
		{
			$result->setStatus(ResultTable::STATUS_OPENED);
		}

		$result->save();

		$this->updateUf($result, $commentId);

		$this->sendPush(self::COMMAND_CREATE, $result);

		return $result;
	}

	/**
	 * @param int $taskId
	 * @param int $commentId
	 * @return Result|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function updateFromComment(int $taskId, int $commentId): ?Result
	{
		$task = $this->loadTask($taskId);
		if (
			!$task
			|| (int)$task->getStatus() === \CTasks::STATE_COMPLETED
		)
		{
			return null;
		}

		$comment = $this->loadComment($commentId);
		if (!$comment)
		{
			return null;
		}

		$result = $this->loadResult($commentId);

		if (
			$result
			&& $result->getStatus() !== ResultTable::STATUS_OPENED
		)
		{
			return null;
		}

		if ($comment->getServiceData() !== self::COMMENT_SERVICE_DATA)
		{
			if ($result)
			{
				$result->delete();
				$this->sendPush(self::COMMAND_DELETE, $result);
			}
			return null;
		}

		$pushCommand = self::COMMAND_UPDATE;

		if (!$result)
		{
			$result = new Result();
			$result->setTaskId($taskId);
			$result->setCommentId($commentId);
			$result->setCreatedBy($this->userId);
			$result->setCreatedAt($comment->getPostDate());
			$result->setStatus(ResultTable::STATUS_OPENED);

			$pushCommand = self::COMMAND_CREATE;
		}

		$result->setText($comment->getPostMessage());
		$result->setUpdatedAt(new DateTime());

		$result->save();

		$this->updateUf($result, $commentId);

		$this->sendPush($pushCommand, $result);

		return $result;
	}

	/**
	 * @param int $taskId
	 * @throws ResultNotFoundException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function close(int $taskId): void
	{
		$res = $this->loadResults($taskId, true);
		if (!$res)
		{
			throw new ResultNotFoundException();
		}

		while ($result = $res->fetchObject())
		{
			$result->setStatus(ResultTable::STATUS_CLOSED);
			$result->save();
		}
	}

	/**
	 * @param int $taskId
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	public function deleteByTaskId(int $taskId)
	{
		$tableName = ResultTable::getTableName();

		$connection = Application::getConnection();
		$connection->query("
			DELETE FROM {$tableName}
			WHERE TASK_ID = {$taskId};
		");
	}

	/**
	 * @param int $commentId
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function deleteByComment(int $commentId)
	{
		$result = $this->loadResult($commentId);
		if (
			!$result
			|| $result->getStatus() !== ResultTable::STATUS_OPENED
		)
		{
			return;
		}

		$result->delete();

		$comment = $this->loadComment($commentId);
		if (
			$comment
			&& $comment->getServiceData() === ResultManager::COMMENT_SERVICE_DATA
		)
		{
			$comment->setServiceData(null);
			$comment->save();
		}

		$this->sendPush(self::COMMAND_DELETE, $result);
	}

	/**
	 * @param int $taskId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getTaskResults(int $taskId): array
	{
		$res = $this->loadResults($taskId);

		$results = [];
		while($result = $res->fetchObject())
		{
			$results[] = $result;
		}
		return $results;
	}

	/**
	 * @param Result $result
	 * @param int $commentId
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	private function updateUf(Result $result, int $commentId)
	{
		if (!Loader::includeModule('disk'))
		{
			return;
		}

		$uf = $this->ufManager->getUserFields(MessageTable::getUfId(), $commentId);

		$diskRelations = [];

		$ufFields = [];
		$ufFields[ResultTable::UF_FILE_NAME] = [];

		if (
			is_array($uf)
			&& array_key_exists('UF_FORUM_MES_URL_PRV', $uf)
			&& $uf['UF_FORUM_MES_URL_PRV']['VALUE']
		)
		{
			$ufFields[ResultTable::UF_PREVIEW_NAME] = (new Signer())->sign($uf['UF_FORUM_MES_URL_PRV']['VALUE'], UrlPreview::SIGN_SALT);
		}

		if (
			is_array($uf)
			&& array_key_exists('UF_FORUM_MESSAGE_DOC', $uf)
			&& !empty($uf['UF_FORUM_MESSAGE_DOC']['VALUE'])
		)
		{
			foreach ($uf['UF_FORUM_MESSAGE_DOC']['VALUE'] as $file)
			{
				$clone = \Bitrix\Tasks\Integration\Disk::cloneFileAttachment([$file]);
				$diskRelations[] = [
					'source' => $file,
					'clone' => $clone[0],
				];
				$ufFields[ResultTable::UF_FILE_NAME] = array_merge($ufFields[ResultTable::UF_FILE_NAME], $clone);
			}
		}

		if (
			is_array($uf)
			&& array_key_exists('UF_FORUM_MESSAGE_VER', $uf)
			&& !empty($uf['UF_FORUM_MESSAGE_VER']['VALUE'])
		)
		{
			$clone = \Bitrix\Tasks\Integration\Disk::cloneFileAttachment([$uf['UF_FORUM_MESSAGE_VER']['VALUE']]);
			$diskRelations[] = [
				'source' => $file,
				'clone' => $clone[0],
			];
			$ufFields[ResultTable::UF_FILE_NAME] = array_merge($ufFields[ResultTable::UF_FILE_NAME], $clone);
		}

		$this->ufManager->Update(ResultTable::getUfId(), $result->getId(), $ufFields);

		$this->updateInlineFiles($result, $diskRelations);
	}

	/**
	 * @param Result $result
	 * @param array $relations
	 */
	private function updateInlineFiles(Result $result, array $relations)
	{
		if (empty($relations))
		{
			return;
		}

		$searchTpl = '[DISK FILE ID=%s]';

		$search = [];
		$replace = [];

		foreach ($relations as $relation)
		{
			$search[] = sprintf($searchTpl, $relation['source']);
			$replace[] = sprintf($searchTpl, $relation['clone']);

			if (!preg_match('/^'.\Bitrix\Disk\Uf\FileUserType::NEW_FILE_PREFIX.'/', $relation['source']))
			{
				$attachedObject = AttachedObject::loadById($relation['source']);
				if($attachedObject)
				{
					$search[] = sprintf($searchTpl, \Bitrix\Disk\Uf\FileUserType::NEW_FILE_PREFIX.$attachedObject->getObjectId());
					$replace[] = sprintf($searchTpl, $relation['clone']);
				}
			}
		}

		$text = $result->getText();
		$text = str_replace($search, $replace, $text);

		$result->setText($text);

		$result->save();
	}

	/**
	 * @param int $taskId
	 * @return TaskObject|null
	 */
	private function loadTask(int $taskId): ?TaskObject
	{
		return (TaskRegistry::getInstance())->getObject($taskId);
	}

	/**
	 * @param int $commentId
	 * @return \Bitrix\Forum\EO_Message
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function loadComment(int $commentId)
	{
		return MessageTable::getById($commentId)->fetchObject();
	}

	/**
	 * @param int $commentId
	 * @return Result|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function loadResult(int $commentId): ?Result
	{
		return ResultTable::GetList([
			'filter' => [
				'=COMMENT_ID' => $commentId,
			],
			'limit' => 1,
		])->fetchObject();
	}

	/**
	 * @param int $taskId
	 * @return \Bitrix\Main\ORM\Query\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function loadResults(int $taskId, bool $onlyOpened = false)
	{
		$filter = [];
		$filter['=TASK_ID'] = $taskId;
		if ($onlyOpened)
		{
			$filter['=STATUS'] = ResultTable::STATUS_OPENED;
		}

		return ResultTable::GetList([
			'select' => ['*', 'UF_*'],
			'filter' => $filter,
			'order' => ['ID' => 'DESC'],
		]);
	}

	/**
	 * @param string $command
	 * @param Result $result
	 */
	private function sendPush(string $command, Result $result)
	{
		$recipients = [$this->userId];

		$result = $result->toArray(false);

		PushService::addEvent($recipients, [
			'module_id' => PushService::MODULE_NAME,
			'command' => $command,
			'params' => [
				'result' => $result,
				'taskId' => $result['taskId'],
				'taskRequireResult' => self::requireResult($result['taskId']) ? "Y" : "N",
				'taskHasResult' => self::hasResult($result['taskId']) ? "Y" : "N",
			],
		]);
	}

	/**
	 * @throws ResultSystemException
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function includeModules()
	{
		if (
			!Loader::includeModule('forum')
		)
		{
			throw new ResultSystemException('Unable to load forum module.');
		}
	}
}

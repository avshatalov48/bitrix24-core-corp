<?php
namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Result;
use Bitrix\Tasks\Comments\Task as TaskComments;
use Bitrix\Tasks\Helper\Filter;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Task\CheckListTable;
use Bitrix\Tasks\Internals\Task\CheckListTreeTable as CheckListTreeTable;
use Bitrix\Tasks\Util\Type\DateTime as TasksDateTime;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\UI;

class TaskService implements Errorable
{
	const ERROR_COULD_NOT_ADD_TASK = 'TASKS_TS_01';
	const ERROR_COULD_NOT_UPDATE_TASK = 'TASKS_TS_02';
	const ERROR_COULD_NOT_READ_TASK = 'TASKS_TS_03';
	const ERROR_COULD_NOT_REMOVE_TASK = 'TASKS_TS_04';
	const ERROR_COULD_NOT_ADD_ITEM = 'TASKS_TS_05';
	const ERROR_COULD_NOT_UPDATE_TAGS = 'TASKS_TS_06';
	const ERROR_COULD_NOT_READ_TAGS = 'TASKS_TS_07';
	const ERROR_COULD_NOT_COMPLETE_TASK = 'TASKS_TS_08';
	const ERROR_COULD_NOT_ADD_FILES_TASK = 'TASKS_TS_09';
	const ERROR_COULD_NOT_COUNT_FILES_TASK = 'TASKS_TS_10';
	const ERROR_COULD_NOT_COUNT_CHECKLIST_FILES = 'TASKS_TS_11';
	const ERROR_COULD_NOT_COUNT_COMMENTS_TASK = 'TASKS_TS_12';
	const ERROR_COULD_NOT_CHECK_COMPLETED_TASK = 'TASKS_TS_13';
	const ERROR_COULD_NOT_CONVERT_DESCRIPTION_TASK = 'TASKS_TS_14';
	const ERROR_COULD_NOT_READ_LIST_TASK = 'TASKS_TS_15';
	const ERROR_COULD_NOT_REMOVE_TAGS = 'TASKS_TS_16';

	private $executiveUserId;
	private $application;

	private $errorCollection;


	public function __construct(int $executiveUserId, \CMain $application)
	{
		$this->executiveUserId = $executiveUserId;
		$this->application = $application;

		$this->errorCollection = new ErrorCollection;
	}

	public function createTask(array $taskFields): int
	{
		try
		{
			list($taskFields['TITLE'], $tags) = $this->cleanTagsInTaskName($taskFields['TITLE']);
			$tags = $this->cleanTagsInTaskFields($taskFields['TAGS'], $tags);

			$taskItemObject = \CTaskItem::add($taskFields, $this->executiveUserId, ['DISABLE_BIZPROC_RUN' => true]);
			$taskId = $taskItemObject->getId();

			if ($taskId > 0)
			{
				$this->addTags($taskId, $tags);
			}
			else
			{
				if ($exception = $this->application->getException())
				{
					$this->errorCollection->setError(new Error($exception->getString(), self::ERROR_COULD_NOT_ADD_TASK));
				}
				else
				{
					$this->errorCollection->setError(new Error('Error creating task', self::ERROR_COULD_NOT_ADD_TASK));
				}
			}

			return $taskId;
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();
			$this->errorCollection->setError(new Error($message, self::ERROR_COULD_NOT_ADD_TASK));
			return 0;
		}
	}

	public function updateTagsList(int $taskId, array $inputTags): bool
	{
		try
		{
			$tags = $this->getTagsByTaskIds([$taskId]);
			$this->addTags($taskId, array_merge($tags, $inputTags));
			return true;
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();
			$this->errorCollection->setError(new Error($message, self::ERROR_COULD_NOT_UPDATE_TAGS));
			return false;
		}
	}

	public function removeTags(int $taskId, string $inputTag): bool
	{
		try
		{
			$taskTags = new \CTaskTags();
			$taskTags->delete(['TASK_ID' => $taskId, 'NAME' => $inputTag]);
			return true;
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();
			$this->errorCollection->setError(new Error($message, self::ERROR_COULD_NOT_REMOVE_TAGS));
			return false;
		}
	}

	public function getTaskIdsByFilter(int $groupId): array
	{
		$taskIds = [];

		try
		{
			$filterInstance = Filter::getInstance($this->executiveUserId, $groupId);
			$filter = $filterInstance->process();

			$filter['ONLY_ROOT_TASKS'] = 'N';

			[$rows, $queryObject] = $this->getList([
				'select' => ['ID'],
				'filter' => $filter,
			]);

			if (count($rows) <= 0)
			{
				return $taskIds;
			}

			/**
			 * @var \CTaskItem[] $rows
			 */
			foreach ($rows as $row)
			{
				$taskData = $row->getData();
				$taskIds[] = $taskData['ID'];
			}

			return $taskIds;
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();
			$this->errorCollection->setError(new Error($message, self::ERROR_COULD_NOT_READ_TASK));
			return $taskIds;
		}
	}

	public function getTagsByTaskIds(array $taskIds): array
	{
		try
		{
			$tags = [];
			$queryObject = \CTaskTags::getList([], ['TASK_ID' => $taskIds]);
			while ($tag = $queryObject->fetch())
			{
				if (in_array($tag['TASK_ID'], $taskIds))
				{
					$tags[] = $tag['NAME'];
				}
			}
			return array_unique($tags);
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();
			$this->errorCollection->setError(new Error($message, self::ERROR_COULD_NOT_READ_TAGS));
			return [];
		}
	}

	public function getTagsByUserIds(array $userIds): array
	{
		try
		{
			$tags = [];
			$queryObject = \CTaskTags::getList([], ['USER_ID' => $userIds]);
			while ($tag = $queryObject->fetch())
			{
				$tags[] = $tag['NAME'];
			}
			return array_unique($tags);
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();
			$this->errorCollection->setError(new Error($message, self::ERROR_COULD_NOT_READ_TAGS));
			return [];
		}
	}

	public function changeTask(int $taskId, array $taskFields): bool
	{
		try
		{
			$task = $this->getTaskItemObject($taskId);
			$task->update($taskFields);
			return true;
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();
			$this->errorCollection->setError(new Error($message, self::ERROR_COULD_NOT_UPDATE_TASK));
			return false;
		}
	}

	public function getTaskInfo($taskId): array
	{
		try
		{
			$taskItemObject = $this->getTaskItemObject($taskId);

			return $taskItemObject->getData(false, [
				'select' => [
					'TITLE',
					'RESPONSIBLE_ID',
					'CREATED_BY',
					'TAGS'
				]
			]);
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();
			$this->errorCollection->setError(new Error($message, self::ERROR_COULD_NOT_READ_TASK));
		}

		return [];
	}

	/**
	 * @param $taskId
	 * @return DateTime|null
	 */
	public function getTaskClosedDate($taskId)
	{
		try
		{
			$taskItemObject = $this->getTaskItemObject($taskId);

			$taskData = $taskItemObject->getData(false, [
				'select' => [
					'CLOSED_DATE'
				]
			]);

			if ($taskData['CLOSED_DATE'])
			{
				return TasksDateTime::createFrom($taskData['CLOSED_DATE']);
			}
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();
			$this->errorCollection->setError(new Error($message, self::ERROR_COULD_NOT_READ_TASK));
		}

		return null;
	}

	public function removeTask(int $taskId): bool
	{
		try
		{
			$task = \CTaskItem::getInstance($taskId, $this->executiveUserId);
			$task->delete();
			return true;
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();
			$this->errorCollection->setError(new Error($message, self::ERROR_COULD_NOT_REMOVE_TASK));
			return false;
		}
	}

	public function completeTasks(array $taskIds): bool
	{
		try
		{
			foreach ($taskIds as $taskId)
			{
				$task = \CTaskItem::getInstance($taskId, $this->executiveUserId);
				$task->complete();
			}
			return true;
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();
			$this->errorCollection->setError(new Error($message, self::ERROR_COULD_NOT_COMPLETE_TASK));
			return false;
		}
	}

	public function attachFilesToTask(\CUserTypeManager $manager, int $taskId, array $attachedIds): array
	{
		try
		{
			$ufValue = $manager->getUserFieldValue('TASKS_TASK', 'UF_TASK_WEBDAV_FILES', $taskId);
			if (is_array($ufValue))
			{
				$ufValue = array_merge($ufValue, $attachedIds);
			}
			else
			{
				$ufValue = $attachedIds;
			}
			$manager->update('TASKS_TASK', $taskId, ['UF_TASK_WEBDAV_FILES' => $ufValue]);
			return $ufValue;
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();
			$this->errorCollection->setError(new Error($message, self::ERROR_COULD_NOT_ADD_FILES_TASK));
			return [];
		}
	}

	public function getAttachedFilesCount(\CUserTypeManager $manager, int $taskId): int
	{
		try
		{
			$ufValue = $manager->getUserFieldValue('TASKS_TASK', 'UF_TASK_WEBDAV_FILES', $taskId);
			if (is_array($ufValue))
			{
				return count($ufValue);
			}
			return 0;
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();
			$this->errorCollection->setError(new Error($message, self::ERROR_COULD_NOT_COUNT_FILES_TASK));
			return 0;
		}
	}

	public function getChecklistCounts(int $taskId): array
	{
		try
		{
			$checkList = [];

			$query = new Query(CheckListTable::getEntity());
			$query->setSelect(['TASK_ID', 'IS_COMPLETE', new ExpressionField('CNT', 'COUNT(TASK_ID)')]);
			$query->setFilter(['TASK_ID' => $taskId]);
			$query->setGroup(['TASK_ID', 'IS_COMPLETE']);
			$query->registerRuntimeField('', new ReferenceField(
				'IT',
				CheckListTreeTable::class,
				Join::on('this.ID', 'ref.CHILD_ID')->where('ref.LEVEL', 1),
				['join_type' => 'INNER']
			));

			$result = $query->exec();
			while ($row = $result->fetch())
			{
				$checkList[$row['IS_COMPLETE'] == 'Y' ? 'complete' : 'progress'] = $row['CNT'];
			}

			return $checkList;
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();
			$this->errorCollection->setError(new Error($message, self::ERROR_COULD_NOT_COUNT_CHECKLIST_FILES));
			return [];
		}
	}

	public function getNewCommentsCount(int $taskId): int
	{
		try
		{
			$newComments = Counter::getInstance((int) $this->executiveUserId)->getCommentsCount([$taskId]);
			return $newComments[$taskId];
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();
			$this->errorCollection->setError(new Error($message, self::ERROR_COULD_NOT_COUNT_COMMENTS_TASK));
			return 0;
		}
	}

	public function isCompletedTask(int $taskId): bool
	{
		try
		{
			$queryObject = \CTasks::getList(
				[],
				['ID' => $taskId, '=STATUS' => \CTasks::STATE_COMPLETED],
				['ID'],
				['USER_ID' => $this->executiveUserId]
			);
			return ($queryObject->fetch() ? true : false);
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();
			$this->errorCollection->setError(new Error($message, self::ERROR_COULD_NOT_CHECK_COMPLETED_TASK));
			return false;
		}
	}

	public function convertDescription(string $text, $ufFields): string
	{
		try
		{
			return UI::convertBBCodeToHtml(
				$text,
				[
					'maxStringLen' => 0,
					'USER_FIELDS' => $ufFields
				]
			);
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();
			$this->errorCollection->setError(new Error($message, self::ERROR_COULD_NOT_CONVERT_DESCRIPTION_TASK));
			return '';
		}
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	private function getTaskItemObject($taskId)
	{
		return \CTaskItem::getInstance($taskId, $this->executiveUserId);
	}

	private function getList(array $params): array
	{
		try
		{
			[$rows, $queryObject] = \CTaskItem::fetchList(
				$this->executiveUserId,
				isset($params['order']) ? $params['order'] : [],
				isset($params['filter']) ? $params['filter'] : [],
				isset($params['navigate']) ? $params['navigate'] : [],
				isset($params['select']) ? $params['select'] : []
			);

			return [$rows, $queryObject];
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();
			$this->errorCollection->setError(new Error($message, self::ERROR_COULD_NOT_READ_LIST_TASK));
			return [];
		}
	}

	private function cleanTagsInTaskName(string $name): array
	{
		$tags = [];
		if (isset($name) && preg_match_all('/\s#([^\s,\[\]<>]+)/is', ' '.$name, $matches))
		{
			$name = trim(str_replace($matches[0], '', $name));
			$tags = $matches[1];
		}
		return [$name, $tags];
	}

	private function cleanTagsInTaskFields(array &$fieldTags, array $tags): array
	{
		$tags = array_merge($fieldTags, $tags);
		$fieldTags = [];
		return $tags;
	}

	private function addTags(int $taskId, array $tags): void
	{
		$tasksObject = new \CTasks();
		$tasksObject->addTags($taskId, $this->executiveUserId, $tags, $this->executiveUserId);
	}

	private function setErrors(Result $result, string $code): void
	{
		$this->errorCollection->setError(new Error(implode('; ', $result->getErrorMessages()), $code));
	}
}
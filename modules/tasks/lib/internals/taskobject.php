<?php
namespace Bitrix\Tasks\Internals;

use Bitrix\Main;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Internals\Task\CheckListTable;
use Bitrix\Tasks\Internals\Task\Result\Result;
use Bitrix\Tasks\Internals\Task\Result\ResultManager;
use Bitrix\Tasks\Internals\Task\Result\ResultTable;
use Bitrix\Tasks\Internals\Task\ScenarioTable;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Internals\Task\UtsTasksTaskTable;
use Bitrix\Tasks\Member\MemberService;
use Bitrix\Tasks\Member\Service\TaskMemberService;
use Bitrix\Tasks\Util\Entity\DateTimeField;
use Bitrix\Tasks\Util\Type\DateTime;

/**
 * Class TaskObject
 *
 * @package Bitrix\Tasks\Internals
 */
class TaskObject extends EO_Task
{
	use MemberTrait;

	/**
	 * @param $data
	 * @return TaskObject
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	public static function wakeUpObject($data): TaskObject
	{
		if (!is_array($data))
		{
			return parent::wakeUp($data);
		}

		$fields = TaskTable::getEntity()->getFields();

		$wakeUpData = [];
		$customData = [];
		foreach ($data as $field => $value)
		{
			if (array_key_exists($field, $fields))
			{

				if (
					$fields[$field] instanceof DateTimeField
					&& is_numeric($value)
				)
				{
					$wakeUpData[$field] = DateTime::createFromTimestampGmt($value);
				}
				else
				{
					$wakeUpData[$field] = $value;
				}
			}
			else
			{
				$customData[$field] = $value;
			}
		}

		$object = parent::wakeUp($wakeUpData);
		foreach ($customData as $field => $value)
		{
			$object->customData->set($field, $value);
		}

		return $object;
	}

	/**
	 * @return bool
	 */
	public function isDeleted(): bool
	{
		return $this->getZombie();
	}

	/**
	 * @param int $userId
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function isMuted(int $userId): bool
	{
		return UserOption::isOptionSet($this->getId(), $userId, UserOption\Option::MUTED);
	}

	/**
	 * @return bool
	 */
	public function isExpired(): bool
	{
		$status = (int)$this->getStatus();
		$completedStates = [Status::SUPPOSEDLY_COMPLETED, Status::COMPLETED, Status::DEFERRED];

		if (!$this->getDeadline() || in_array($status, $completedStates, true))
		{
			return false;
		}

		return (DateTime::createFrom($this->getDeadline()))->checkLT(new DateTime());
	}

	/**
	 * @return array
	 */
	public function toArray(): array
	{
		$data = $this->collectValues();
		foreach ($data as $fieldName => $field)
		{
			if (
				$field instanceof Main\ORM\Fields\Relations\Reference
				|| $field instanceof Main\ORM\Fields\Relations\OneToMany
				|| $field instanceof Main\ORM\Fields\Relations\ManyToMany
				|| $field instanceof Main\ORM\Fields\ExpressionField
			)
			{
				continue;
			}

			if ($data[$fieldName] instanceof DateTime)
			{
				$data[$fieldName] = $data[$fieldName]->getTimestamp();
			}

			if (is_object($data[$fieldName]))
			{
				unset($data[$fieldName]);
			}
		}
		return $data;
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function isCrm(): bool
	{
		$params = [
			'select' => ['SCENARIO'],
			'filter' => [
				'=TASK_ID' => $this->getId(),
				'=SCENARIO' => ScenarioTable::SCENARIO_CRM,
			],
			'limit' => 1,
		];
		return !is_null(ScenarioTable::getList($params)->fetchObject());
	}

	public function getCrmFields(): array
	{
		//first of all, try to get crm data from loaded data
		try
		{
			$crmObject = $this->getUtsData() ?? UtsTasksTaskTable::getById($this->getId())->fetchObject();
			if (!is_null($crmObject))
			{
				$ufCrm = $crmObject->getUfCrmTask();
				if (empty($ufCrm))
				{
					return [];
				}

				return unserialize($crmObject->getUfCrmTask(), ['allow_classes' => false]);
			}
		}
		catch (\Exception $exception)
		{
			return [];
		}

		return [];
	}

	public function getFileFields(): array
	{
		//first of all, try to get crm data from loaded data
		$filesObject = $this->getUtsData() ?? UtsTasksTaskTable::getById($this->getId())->fetchObject();
		if (!is_null($filesObject))
		{
			$ufFiles = $filesObject->getUfTaskWebdavFiles();
			if (empty($ufFiles))
			{
				return [];
			}
			$ufFiles = unserialize($ufFiles, ['allow_classes' => false]);

			return is_array($ufFiles) ? $ufFiles : [];
		}

		return [];
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getLastResult(): ?Result
	{
		$results = $this->getResult() ?? ResultTable::getByTaskId($this->getId());
		if ($results->count() !== 0)
		{
			$ids = $results->getIdList();
			return $results->getByPrimary(max($ids));
		}

		return null;
	}

	public function getFirstResult(): ?Result
	{
		$results = $this->getResult() ?? ResultTable::getByTaskId($this->getId());
		if ($results->count() !== 0)
		{
			$ids = $results->getIdList();
			return $results->getByPrimary(min($ids));
		}

		return null;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function hasChecklist(): bool
	{
		$checklist = $this->getChecklistData() ?? CheckListTable::getByTaskId($this->getId());

		return !is_null($checklist);
	}

	public function getRealStatus(): ?int
	{
		$params = [
			'select' => ['STATUS'],
		];
		$task = TaskTable::getByPrimary($this->getId(), $params)->fetchObject();
		return $task ? $task->getStatus(): null;
	}

	public function isCompleted(): bool
	{
		return Status::COMPLETED === (int)$this->getStatus();
	}

	public function isResultRequired(): bool
	{
		return ResultManager::requireResult($this->getId());
	}

	public function getMemberService(): MemberService
	{
		return new TaskMemberService($this->getId());
	}
}
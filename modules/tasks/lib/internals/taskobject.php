<?php
namespace Bitrix\Tasks\Internals;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\ManyToMany;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Tasks\Integration\SocialNetwork\Exception\SocialnetworkException;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Internals\Log\LogFacade;
use Bitrix\Tasks\Internals\Task\CheckListTable;
use Bitrix\Tasks\Internals\Task\RegularParametersObject;
use Bitrix\Tasks\Internals\Task\RegularParametersTable;
use Bitrix\Tasks\Internals\Task\Result\Result;
use Bitrix\Tasks\Internals\Task\Result\ResultManager;
use Bitrix\Tasks\Internals\Task\Result\ResultTable;
use Bitrix\Tasks\Internals\Task\ScenarioTable;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Internals\Task\UtsTasksTaskTable;
use Bitrix\Tasks\Member\AbstractMemberService;
use Bitrix\Tasks\Member\Service\TaskMemberService;
use Bitrix\Tasks\Util\Entity\DateTimeField;
use Bitrix\Tasks\Util\Type\DateTime;

class TaskObject extends EO_Task implements Arrayable
{
	use MemberTrait;
	use WakeUpTrait;

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getCrmFields(bool $forceFetch = true): array
	{
		//first of all, try to get crm data from loaded data
		$crmObject = $this->getUtsData();
		if (is_null($crmObject) && $forceFetch)
		{
			$crmObject = UtsTasksTaskTable::getById($this->getId())->fetchObject();
		}
		if (empty($crmObject?->getUfCrmTask()))
		{
			return [];
		}
		return unserialize($crmObject->getUfCrmTask(), ['allowed_classes' => false]);
	}

	public function getRegularFields(bool $forceFetch = true): ?RegularParametersObject
	{
		//first of all, try to get crm data from loaded data
		$regularObject = $this->getRegular();
		if (is_null($regularObject) && $forceFetch)
		{
			$regularObject = RegularParametersTable::getByTaskId($this->getId());
		}

		return $regularObject;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getRegularDeadlineOffset(): ?int
	{
		if (!$this->isRegular())
		{
			return null;
		}

		$regularFields = $this->getRegularFields()?->getRegularParameters();
		if (is_null($regularFields))
		{
			return null;
		}

		return (int)$regularFields['DEADLINE_OFFSET'];
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
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
			$ufFiles = unserialize($ufFiles, ['allowed_classes' => false]);

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

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
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

	public function getRealStatus(): ?int
	{
		return $this->fillStatus();
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getRegularityStartTime(): ?\Bitrix\Main\Type\DateTime
	{
		if (!$this->isRegular())
		{
			return null;
		}

		return $this->getRegularFields()?->getStartTime();
	}

	public function isDeleted(): bool
	{
		return $this->getZombie();
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function isMuted(int $userId): bool
	{
		return UserOption::isOptionSet($this->getId(), $userId, UserOption\Option::MUTED);
	}

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

	public function isCrm(): bool
	{
		return (bool)$this->fillScenario()?->isCrm();
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function isRegular(): bool
	{
		return
			$this->getIsRegular()
			?? TaskTable::getByPrimary($this->getId(), ['select' => ['ID', 'IS_REGULAR']])->fetchObject()->getIsRegular();
	}

	public function isCompleted(): bool
	{
		return Status::COMPLETED === (int)$this->getStatus();
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function isResultRequired(): bool
	{
		return ResultManager::requireResult($this->getId());
	}

	public function isInGroup(bool $forceFetch = true): bool
	{
		$forceFetch && $this->fillGroupId();
		return $this->getGroupId() !== 0;
	}

	public function isInGroupStage(): bool
	{
		$this->fillStageId();
		return $this->getStageId() !== 0;
	}

	public function isNew(): bool
	{
		return $this->getId() <= 0;
	}

	public function isScrum(): bool
	{
		if ($this->getGroupId() <= 0)
		{
			return false;
		}

		try
		{
			return (bool)Group::getById($this->getGroupId())?->isScrumProject();
		}
		catch (SocialnetworkException $exception)
		{
			LogFacade::logThrowable($exception);
			return false;
		}
	}

	public function hasDeadlineValue(): bool
	{
		return parent::hasDeadline() && !is_null($this->getDeadline());
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

	public function toArray(): array
	{
		$data = $this->collectValues();
		foreach ($data as $fieldName => $field)
		{
			if (
				$field instanceof Reference
				|| $field instanceof OneToMany
				|| $field instanceof ManyToMany
				|| $field instanceof ExpressionField
			)
			{
				continue;
			}

			if ($field instanceof DateTime)
			{
				$data[$fieldName] = $field->getTimestamp();
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
	public function getChildren(): TaskCollection
	{
		$query = $this::$dataClass::query();
		$query
			->setSelect(['ID', 'PARENT_ID', 'TITLE'])
			->where('PARENT_ID', $this->getId());

		return $query->exec()->fetchCollection();
	}

	public function getMemberService(): AbstractMemberService
	{
		return new TaskMemberService($this->getId());
	}
}

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
use Bitrix\Socialnetwork\Collab\Registry\CollabRegistry;
use Bitrix\Tasks\Integration\SocialNetwork\Collab\Provider\CollabProvider;
use Bitrix\Tasks\Integration\SocialNetwork\Exception\SocialnetworkException;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Internals\Log\LogFacade;
use Bitrix\Tasks\Internals\Task\CheckListTable;
use Bitrix\Tasks\Internals\Task\RegularParametersObject;
use Bitrix\Tasks\Internals\Task\Result\Result;
use Bitrix\Tasks\Internals\Task\Result\ResultManager;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Member\AbstractMemberService;
use Bitrix\Tasks\Member\Service\TaskMemberService;
use Bitrix\Tasks\Util\Type\DateTime;
use Throwable;

class TaskObject extends EO_Task implements Arrayable
{
	use CacheTrait;
	use MemberTrait;
	use WakeUpTrait;

	public function getCrmFields(bool $force = false): array
	{
		if (!$this->isUtsDataFilled() || $this->isUtsDataChanged() || $force)
		{
			$this->fillUtsData()?->fillUfCrmTask();
		}

		return (array)$this->getUtsData()?->getUfCrmTask();
	}

	public function getRegularFields(bool $force = false): ?RegularParametersObject
	{
		if (!$this->isRegularFilled() || $this->isRegularChanged() || $force)
		{
			$this->fillRegular();
		}

		return $this->getRegular();
	}
	
	public function getRegularDeadlineOffset(): int
	{
		if (!$this->isRegular())
		{
			return 0;
		}

		$regularFields = $this->getRegularFields()?->getRegularParameters();
		if (is_null($regularFields))
		{
			return 0;
		}

		return (int)$regularFields['DEADLINE_OFFSET'];
	}

	public function getFileFields(bool $force = false): array
	{
		if (!$this->isUtsDataFilled() || $this->isUtsDataChanged() || $force)
		{
			$this->fillUtsData()?->fillUfTaskWebdavFiles();
		}

		return (array)$this->getUtsData()?->getUfTaskWebdavFiles();
	}

	public function getLastResult(): ?Result
	{
		$results = $this->getResult() ?? $this->fillResult();
		if ((int)$results?->count() !== 0)
		{
			$ids = $results->getIdList();
			return $results->getByPrimary(max($ids));
		}

		return null;
	}

	public function getFirstResult(): ?Result
	{
		$results = $this->getResult() ?? $this->fillResult();
		if ((int)$results?->count() !== 0)
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

	public function getRegularityStartTime(): ?\Bitrix\Main\Type\DateTime
	{
		if (!$this->isRegular())
		{
			return null;
		}

		return $this->getRegularFields()?->getStartTime();
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

	public function getCachedCrmFields(): array
	{
		return $this->getCached('CRM_FIELDS') ?? [];
	}

	public function getFlowId(bool $force = true): int
	{
		if ($force && $this->onFlow() === false)
		{
			return 0;
		}

		return (int)$this->getFlowTask()?->getFlowId();
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
		return $this->fillCache('MUTED', UserOption::isOptionSet($this->getId(), $userId, UserOption\Option::MUTED));
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
	
	public function isRegular(): bool
	{
		return $this->getIsRegular() ?? $this->fillIsRegular();
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

	public function isInGroup(bool $force = true): bool
	{
		$force && $this->fillGroupId();
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

	public function isCollab(): bool
	{
		if ($this->getGroupId() <= 0)
		{
			return false;
		}

		return (bool)CollabProvider::getInstance()?->isCollab($this->getGroupId());
	}

	public function onFlow(): bool
	{
		return $this->fillFlowTask() !== null;
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

	public function cacheCrmFields(): static
	{
		$currentCrmFields = $this->fillUtsData()?->fillUfCrmTask();
		$currentCrmFields = !is_array($currentCrmFields) ? [] : $currentCrmFields;
		$this->cache('CRM_FIELDS', $currentCrmFields);

		return $this;
	}

	public function fillAdditionalMembers(): static
	{
		$this->disablePrefix();

		if (!$this->isCached('ACCOMPLICES'))
		{
			$this->cache('ACCOMPLICES', $this->getFacade()->getAccompliceMembersIds());
		}
		if (!$this->isCached('AUDITORS'))
		{
			$this->cache('AUDITORS', $this->getFacade()->getAuditorMembersIds());
		}

		$this->enablePrefix();

		return $this;
	}

	public function toArray(bool $withCustom = false): array
	{
		$data = $this->collectValues();
		$data = $withCustom ? array_merge($data, $this->customData->toArray()) : $data;
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
}

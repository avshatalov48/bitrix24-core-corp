<?php

namespace Bitrix\Tasks\Scrum\Form;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Type\DateTime;

class EntityForm
{
	const BACKLOG_TYPE = 'backlog';
	const SPRINT_TYPE = 'sprint';

	const SPRINT_ACTIVE = 'active';
	const SPRINT_PLANNED = 'planned';
	const SPRINT_COMPLETED = 'completed';

	const STATE_COMPLETED_IN_ACTIVE_SPRINT = 'completedInActiveSprint';

	private $id = 0;
	private $groupId = 0;
	private $entityType = '';
	private $name = '';
	private $sort = null;
	private $createdBy = 0;
	private $modifiedBy = 0;
	private $dateStart = null;
	private $dateEnd = null;
	private $status = '';
	private $info = null;

	/** @var ItemForm[] */
	private $children = [];
	private $tmpId = '';

	/**
	 * Returns an array with keys for the client.
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'groupId' => $this->getGroupId(),
			'entityType' => $this->getEntityType(),
			'name' => $this->getName(),
			'sort' => $this->getSort(),
			'createdBy' => $this->getCreatedBy(),
			'modifiedBy' => $this->getModifiedBy(),
			'dateStart' => $this->getDateStart(),
			'dateEnd' => $this->getDateEnd(),
			'status' => $this->getStatus(),
			'info' => $this->getInfo(),
			'children' => $this->getChildren(),
			'tmpId' => $this->getTmpId(),
		];
	}

	/**
	 * Returns an array with keys for the REST.
	 *
	 * @return array
	 */
	public function toRest(): array
	{
		return [
			'id' => $this->getId(),
			'groupId' => $this->getGroupId(),
			'entityType' => $this->getEntityType(),
			'name' => $this->getName(),
			'goal' => $this->getInfo()->getSprintGoal(),
			'sort' => $this->getSort(),
			'createdBy' => $this->getCreatedBy(),
			'modifiedBy' => $this->getModifiedBy(),
			'dateStart' => $this->getDateStart(),
			'dateEnd' => $this->getDateEnd(),
			'status' => $this->getStatus(),
		];
	}

	/**
	 * Checks if an object is empty based on an Id. If id empty, it means that it was not possible to get data
	 * from the storage or did not fill out the id.
	 *
	 * @return bool
	 */
	public function isEmpty(): bool
	{
		return (empty($this->id));
	}

	/**
	 * The method checks if the content of the entity (Backlog/Sprint) is hidden for the specified user.
	 *
	 * @param int $userId User id.
	 * @return bool
	 */
	public function isShownContent(int $userId): bool
	{
		return $this->getInfo()->isVisibility($userId);
	}

	/**
	 * Makes the content visible to the specified user.
	 *
	 * @param int $userId User id.
	 * @return void
	 */
	public function showContent(int $userId): void
	{
		$this->getInfo()->showVisibility($userId);
	}

	/**
	 * Makes the content invisible to the specified user.
	 *
	 * @param int $userId User id.
	 * @return void
	 */
	public function hideContent(int $userId): void
	{
		$this->getInfo()->hideVisibility($userId);
	}

	public function isActiveSprint(): bool
	{
		return ($this->status === self::SPRINT_ACTIVE);
	}

	public function isPlannedSprint(): bool
	{
		return ($this->status === self::SPRINT_PLANNED);
	}

	public function isCompletedSprint(): bool
	{
		return ($this->status === self::SPRINT_COMPLETED);
	}

	/**
	 * Returns a list of fields to create a sprint.
	 *
	 * @return array
	 * @throws ArgumentNullException
	 */
	public function getFieldsToCreateSprint(): array
	{
		$this->checkRequiredParametersToCreateSprint();

		return [
			'GROUP_ID' => $this->getGroupId(),
			'ENTITY_TYPE' => self::SPRINT_TYPE,
			'NAME' => $this->getName(),
			'SORT' => $this->getSort(),
			'CREATED_BY' => $this->getCreatedBy(),
			'MODIFIED_BY' => $this->getCreatedBy(),
			'DATE_START' => $this->getDateStart(),
			'DATE_END' => $this->getDateEnd(),
			'STATUS' => $this->getStatus() === '' ? self::SPRINT_PLANNED : $this->getStatus(),
		];
	}

	/**
	 * Returns a list of fields to create a backlog.
	 *
	 * @return array
	 * @throws ArgumentNullException
	 */
	public function getFieldsToCreateBacklog(): array
	{
		$this->checkRequiredParametersToCreateBacklog();

		return [
			'GROUP_ID' => $this->getGroupId(),
			'ENTITY_TYPE' => self::BACKLOG_TYPE,
			'CREATED_BY' => $this->getCreatedBy(),
			'MODIFIED_BY' => $this->getCreatedBy(),
		];
	}

	/**
	 * Returns a list of fields to update an entity.
	 *
	 * @return array
	 */
	public function getFieldsToUpdateEntity(): array
	{
		$fields = [];

		if ($this->groupId)
		{
			$fields['GROUP_ID'] = $this->groupId;
		}

		if ($this->name)
		{
			$fields['NAME'] = $this->name;
		}

		if ($this->sort !== null)
		{
			$fields['SORT'] = $this->sort;
		}

		if ($this->modifiedBy)
		{
			$fields['MODIFIED_BY'] = $this->modifiedBy;
		}

		if ($this->dateStart)
		{
			$fields['DATE_START'] = $this->dateStart;
		}

		if ($this->dateEnd)
		{
			$fields['DATE_END'] = $this->dateEnd;
		}

		if ($this->status)
		{
			$fields['STATUS'] = $this->status;
		}

		if ($this->info)
		{
			$fields['INFO'] = $this->info;
		}

		return $fields;
	}

	/**
	 * To fill the object with data obtained from the database.
	 *
	 * @param array $fields An array with fields.
	 */
	public function fillFromDatabase(array $fields): void
	{
		if ($fields['ID'] ?? null)
		{
			$this->setId($fields['ID']);
		}
		if ($fields['GROUP_ID'] ?? null)
		{
			$this->setGroupId($fields['GROUP_ID']);
		}
		if ($fields['ENTITY_TYPE'] ?? null)
		{
			$this->setEntityType($fields['ENTITY_TYPE']);
		}
		if ($fields['NAME'] ?? null)
		{
			$this->setName($fields['NAME']);
		}
		if ($fields['SORT'] ?? null)
		{
			$this->setSort($fields['SORT']);
		}
		if ($fields['CREATED_BY'] ?? null)
		{
			$this->setCreatedBy($fields['CREATED_BY']);
		}
		if ($fields['MODIFIED_BY'] ?? null)
		{
			$this->setModifiedBy($fields['MODIFIED_BY']);
		}
		if ($fields['DATE_START'] ?? null)
		{
			$this->setDateStart($fields['DATE_START']);
		}
		if ($fields['DATE_END'] ?? null)
		{
			$this->setDateEnd($fields['DATE_END']);
		}
		if ($fields['STATUS'] ?? null)
		{
			$this->setStatus($fields['STATUS']);
		}
		if ($fields['INFO'] ?? null)
		{
			$this->setInfo($fields['INFO']);
		}
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId($id): void
	{
		$this->id = (is_numeric($id) ? (int) $id : 0);
	}

	public function getGroupId(): int
	{
		return $this->groupId;
	}

	public function setGroupId($groupId): void
	{
		$this->groupId = (is_numeric($groupId) ? (int) $groupId : 0);
	}

	public function getEntityType(): string
	{
		return $this->entityType;
	}

	public function setEntityType($entityType): void
	{
		$this->entityType = (is_string($entityType) ? $entityType : '');
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName($name): void
	{
		$this->name = (is_string($name) ? $name : '');
	}

	public function getSort(): int
	{
		return $this->sort === null ? 0 : $this->sort;
	}

	public function setSort($sort): void
	{
		$this->sort = (is_numeric($sort) ? (int) $sort : 0);
	}

	public function getCreatedBy(): int
	{
		return $this->createdBy;
	}

	public function setCreatedBy($createdBy): void
	{
		$this->createdBy = (is_numeric($createdBy) ? (int) $createdBy : 0);
	}

	public function getModifiedBy(): int
	{
		return $this->modifiedBy;
	}

	public function setModifiedBy($modifiedBy): void
	{
		$this->modifiedBy = (is_numeric($modifiedBy) ? (int) $modifiedBy : 0);
	}

	public function getDateStart(): DateTime
	{
		return ($this->dateStart === null ? new Datetime() : $this->dateStart);
	}

	public function setDateStart(Datetime $dateStart): void
	{
		$this->dateStart = $dateStart;
	}

	public function getDateEnd(): Datetime
	{
		$currentDateTime = new Datetime();

		if ($this->dateEnd)
		{
			return $this->dateEnd;
		}
		else
		{
			return $currentDateTime;
		}
	}

	public function setDateEnd(Datetime $dateEnd): void
	{
		$this->dateEnd = $dateEnd;
	}

	public function getStatus(): string
	{
		return $this->status;
	}

	public function setStatus(string $status): void
	{
		$listAvailableStatuses = [
			self::SPRINT_ACTIVE,
			self::SPRINT_PLANNED,
			self::SPRINT_COMPLETED,
		];

		if (in_array($status, $listAvailableStatuses, true))
		{
			$this->status = $status;
		}
	}

	public function getInfo(): EntityInfo
	{
		return ($this->info === null ? new EntityInfo() : $this->info);
	}

	public function setInfo(EntityInfo $entityInfo): void
	{
		$this->info = $entityInfo;
	}

	public function getChildren(): array
	{
		return $this->children;
	}

	public function setChildren(array $children): void
	{
		foreach ($children as $child)
		{
			if ($child instanceof ItemForm)
			{
				$this->children[] = $child;
			}
		}
	}

	public function getTmpId(): string
	{
		return $this->tmpId;
	}

	public function setTmpId($tmpId): void
	{
		$this->tmpId = (is_string($tmpId) ? $tmpId : '');
	}

	/**
	 * @throws ArgumentNullException
	 */
	private function checkRequiredParametersToCreateSprint(): void
	{
		if (empty($this->groupId))
		{
			throw new ArgumentNullException('groupId');
		}

		if (empty($this->name))
		{
			throw new ArgumentNullException('name');
		}

		if (empty($this->createdBy))
		{
			throw new ArgumentNullException('createdBy');
		}
	}

	/**
	 * @throws ArgumentNullException
	 */
	private function checkRequiredParametersToCreateBacklog(): void
	{
		if (empty($this->groupId))
		{
			throw new ArgumentNullException('groupId');
		}

		if (empty($this->createdBy))
		{
			throw new ArgumentNullException('createdBy');
		}
	}
}
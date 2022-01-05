<?php
namespace Bitrix\Tasks\Scrum\Internal;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Validators;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;

/**
 * Class EntityTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Entity_Query query()
 * @method static EO_Entity_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Entity_Result getById($id)
 * @method static EO_Entity_Result getList(array $parameters = array())
 * @method static EO_Entity_Entity getEntity()
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Entity createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Entity_Collection createCollection()
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Entity wakeUpObject($row)
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Entity_Collection wakeUpCollection($rows)
 */
class EntityTable extends Entity\DataManager
{
	const BACKLOG_TYPE = 'backlog';
	const SPRINT_TYPE = 'sprint';

	const SPRINT_ACTIVE = 'active';
	const SPRINT_PLANNED = 'planned';
	const SPRINT_COMPLETED = 'completed';

	const STATE_COMPLETED_IN_ACTIVE_SPRINT = 'completedInActiveSprint';

	private $id;
	private $groupId;
	private $entityType;
	private $name;
	private $sort;
	private $createdBy;
	private $modifiedBy;
	private $dateStart;
	private $dateEnd;
	private $status;

	/**
	 * @var EntityInfoColumn
	 */
	private $info;

	private $children = [];

	private $tmpId = '';

	public static function createEntityObject(array $fields = []): EntityTable
	{
		$entityObject = new self();

		if ($fields)
		{
			$entityObject = self::fillItemObjectByData($entityObject, $fields);
		}

		return $entityObject;
	}

	public static function getTableName()
	{
		return 'b_tasks_scrum_entity';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 * @throws ArgumentTypeException
	 * @throws SystemException
	 */
	public static function getMap()
	{
		$id = new Fields\IntegerField('ID');
		$id->configurePrimary(true);
		$id->configureAutocomplete(true);

		$groupId = new Fields\IntegerField('GROUP_ID');

		$entityType = new Fields\EnumField('ENTITY_TYPE');
		$entityType->addValidator(new Validators\LengthValidator(1, 20));
		$entityType->configureValues([
			self::BACKLOG_TYPE,
			self::SPRINT_TYPE
		]);
		$entityType->configureDefaultValue(self::SPRINT_TYPE);

		$name = new Fields\StringField('NAME');
		$name->addValidator(new Validators\LengthValidator(null, 255));

		$sort = new Fields\IntegerField('SORT');

		$createdBy = new Fields\IntegerField('CREATED_BY');

		$modifiedBy = new Fields\IntegerField('MODIFIED_BY');

		$dateStart = new Fields\DatetimeField('DATE_START');

		$dateEnd = new Fields\DatetimeField('DATE_END');

		//todo add default timezone from server and user
		$dateStartTz = new Fields\StringField('DATE_START_TZ');
		$dateStartTz->addValidator(new Validators\LengthValidator(null, 50));
		$dateEndTz = new Fields\StringField('DATE_END_TZ');
		$dateEndTz->addValidator(new Validators\LengthValidator(null, 50));

		$status = new Fields\EnumField('STATUS');
		$status->addValidator(new Validators\LengthValidator(null, 20));
		$status->configureValues([
			self::SPRINT_ACTIVE,
			self::SPRINT_PLANNED,
			self::SPRINT_COMPLETED
		]);

		$info = new Fields\ObjectField('INFO');
		$info->configureObjectClass(EntityInfoColumn::class);
		$info->configureSerializeCallback(function (?EntityInfoColumn $entityInfoColumn)
		{
			return $entityInfoColumn ? Json::encode($entityInfoColumn->getInfoData()) : [];
		});
		$info->configureUnserializeCallback(function ($value)
		{
			$data = (is_string($value) && !empty($value) ? Json::decode($value) : []);

			$entityInfoColumn = new EntityInfoColumn();
			$entityInfoColumn->setInfoData($data);

			return $entityInfoColumn;
		});

		$items = new OneToMany('ITEMS', ItemTable::class, 'ENTITY');
		$items->configureJoinType(Join::TYPE_LEFT);

		return [
			$id,
			$groupId,
			$entityType,
			$name,
			$sort,
			$createdBy,
			$modifiedBy,
			$dateStart,
			$dateEnd,
			$dateStartTz,
			$dateEndTz,
			$status,
			$info,
			$items
		];
	}

	/**
	 * Group deletion handler.
	 *
	 * @param int $groupId Group id.
	 * @return bool
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function OnSocNetGroupDelete($groupId)
	{
		$groupId = (int) $groupId;

		if ($groupId > 0)
		{
			self::deleteByGroupId($groupId);
		}

		return true;
	}

	/**
	 * Deletes an item by group id.
	 *
	 * @param int $groupId Group id.
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function deleteByGroupId(int $groupId)
	{
		$connection = Application::getConnection();

		$queryObjectResult = $connection->query(
			'SELECT ID FROM '.self::getTableName().' WHERE GROUP_ID = '.(int) $groupId
		);
		while ($entity = $queryObjectResult->fetch())
		{
			ItemTable::deleteByEntityId($entity['ID']);
		}

		$connection->queryExecute('DELETE FROM '.self::getTableName().' WHERE GROUP_ID = '.(int) $groupId);
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
			'GROUP_ID' => $this->groupId,
			'ENTITY_TYPE' => self::SPRINT_TYPE,
			'NAME' => $this->name,
			'SORT' => $this->sort,
			'CREATED_BY' => $this->createdBy,
			'MODIFIED_BY' => $this->createdBy,
			'DATE_START' => $this->dateStart,
			'DATE_END' => $this->dateEnd,
			'STATUS' => ($this->status ? $this->status : self::SPRINT_PLANNED)
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
			'GROUP_ID' => $this->groupId,
			'ENTITY_TYPE' => self::BACKLOG_TYPE,
			'CREATED_BY' => $this->createdBy,
			'MODIFIED_BY' => $this->createdBy
		];
	}

	/**
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
	 * Checks if an object is empty based on an Id. If id empty, it means that it was not possible to get data
	 * from the storage or did not fill out the id.
	 *
	 * @return bool
	 */
	public function isEmpty(): bool
	{
		return (empty($this->id));
	}

	public function isActiveSprint(): bool
	{
		return ($this->status == self::SPRINT_ACTIVE);
	}

	public function isPlannedSprint(): bool
	{
		return ($this->status == self::SPRINT_PLANNED);
	}

	public function isCompletedSprint(): bool
	{
		return ($this->status == self::SPRINT_COMPLETED);
	}

	public function getId(): int
	{
		return ($this->id ? $this->id : 0);
	}

	public function setId(int $id): void
	{
		$this->id = (int) $id;
	}

	public function getTmpId(): string
	{
		return $this->tmpId;
	}

	public function setTmpId(string $tmpId): void
	{
		$this->tmpId = $tmpId;
	}

	public function getGroupId(): int
	{
		return $this->groupId ? $this->groupId : 0;
	}

	public function setGroupId($groupId): void
	{
		$this->groupId = $groupId;
	}

	public function getEntityType(): string
	{
		return ($this->entityType ? $this->entityType : '');
	}

	public function setEntityType(string $entityType): void
	{
		$this->entityType = $entityType;
	}

	public function getName(): string
	{
		return ($this->name ? $this->name : '');
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function getSort(): int
	{
		return ($this->sort ? $this->sort : 0);
	}

	public function setSort(int $sort): void
	{
		$this->sort = (int) $sort;
	}

	public function getCreatedBy(): int
	{
		return ($this->createdBy ? $this->createdBy : 0);
	}

	public function setCreatedBy($createdBy): void
	{
		$this->createdBy = $createdBy;
	}

	public function getModifiedBy(): int
	{
		return ($this->modifiedBy ? $this->modifiedBy : 0);
	}

	public function setModifiedBy(int $modifiedBy): void
	{
		$this->modifiedBy = (int) $modifiedBy;
	}

	public function getDateStart(): DateTime
	{
		return ($this->dateStart ? $this->dateStart : new Datetime());
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
		return ($this->status ? $this->status : '');
	}

	public function setStatus(string $status): void
	{
		$listAvailableStatuses = [
			self::SPRINT_ACTIVE,
			self::SPRINT_PLANNED,
			self::SPRINT_COMPLETED
		];
		if (in_array($status, $listAvailableStatuses, true))
		{
			$this->status = $status;
		}
	}

	public function getInfo(): EntityInfoColumn
	{
		return ($this->info ? $this->info : new EntityInfoColumn());
	}

	public function setInfo(EntityInfoColumn $entityInfoColumn): void
	{
		$this->info = $entityInfoColumn;
	}

	public function getChildren(): array
	{
		return $this->children;
	}

	public function setChildren(array $children): void
	{
		foreach ($children as $child)
		{
			if ($child instanceof ItemTable)
			{
				$this->children[] = $child;
			}
		}
	}

	/**
	 * @throws ArgumentNullException
	 */
	private function checkRequiredParametersToCreateSprint(): void
	{
		if (empty($this->groupId))
		{
			throw new ArgumentNullException('GROUP_ID');
		}

		if (empty($this->name))
		{
			throw new ArgumentNullException('NAME');
		}

		if (empty($this->createdBy))
		{
			throw new ArgumentNullException('CREATED_BY');
		}
	}

	/**
	 * @throws ArgumentNullException
	 */
	private function checkRequiredParametersToCreateBacklog(): void
	{
		if (empty($this->groupId))
		{
			throw new ArgumentNullException('GROUP_ID');
		}

		if (empty($this->createdBy))
		{
			throw new ArgumentNullException('CREATED_BY');
		}
	}

	private static function fillItemObjectByData(EntityTable $entity, array $entityData): EntityTable
	{
		if ($entityData['ID'])
		{
			$entity->setId($entityData['ID']);
		}
		if ($entityData['GROUP_ID'])
		{
			$entity->setGroupId($entityData['GROUP_ID']);
		}
		if ($entityData['ENTITY_TYPE'])
		{
			$entity->setEntityType($entityData['ENTITY_TYPE']);
		}
		if ($entityData['NAME'])
		{
			$entity->setName($entityData['NAME']);
		}
		if ($entityData['SORT'])
		{
			$entity->setSort($entityData['SORT']);
		}
		if ($entityData['CREATED_BY'])
		{
			$entity->setCreatedBy($entityData['CREATED_BY']);
		}
		if ($entityData['MODIFIED_BY'])
		{
			$entity->setModifiedBy($entityData['MODIFIED_BY']);
		}
		if ($entityData['DATE_START'])
		{
			$entity->setDateStart($entityData['DATE_START']);
		}
		if ($entityData['DATE_END'])
		{
			$entity->setDateEnd($entityData['DATE_END']);
		}
		if ($entityData['STATUS'])
		{
			$entity->setStatus($entityData['STATUS']);
		}
		if ($entityData['INFO'])
		{
			$entity->setInfo($entityData['INFO']);
		}

		return $entity;
	}
}
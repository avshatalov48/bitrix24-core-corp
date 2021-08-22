<?php
namespace Bitrix\Tasks\Scrum\Internal;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Scrum\Service\PushService;

/**
 * Class ItemTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Item_Query query()
 * @method static EO_Item_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Item_Result getById($id)
 * @method static EO_Item_Result getList(array $parameters = array())
 * @method static EO_Item_Entity getEntity()
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Item createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Item_Collection createCollection()
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Item wakeUpObject($row)
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Item_Collection wakeUpCollection($rows)
 */
class ItemTable extends Entity\DataManager
{
	const TASK_TYPE = 'task';
	const EPIC_TYPE = 'epic';

	private $id;
	private $entityId;
	private $typeId;
	private $active;
	private $name;
	private $description;
	private $itemType;
	private $parentId;
	private $sort;
	private $createdBy;
	private $modifiedBy;
	private $storyPoints;
	private $sourceId;

	/**
	 * @var ItemInfoColumn
	 */
	private $info;

	private $tmpId = '';

	public static function createItemObject(array $fields = []): ItemTable
	{
		$itemObject = new self();

		if ($fields)
		{
			$itemObject = self::fillItemObjectByData($itemObject, $fields);
		}

		return $itemObject;
	}

	public static function getTableName()
	{
		return 'b_tasks_scrum_item';
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

		$entityId = new Fields\IntegerField('ENTITY_ID');

		$typeId = new Fields\IntegerField('TYPE_ID');

		$active = new Fields\StringField('ACTIVE');
		$active->addValidator(new Validators\LengthValidator(1, 1));
		$active->configureDefaultValue('Y');

		$name = new Fields\StringField('NAME');
		$name->addValidator(new Validators\LengthValidator(null, 255));

		$description = new Fields\TextField('DESCRIPTION');

		$itemType = new Fields\EnumField('ITEM_TYPE');
		$itemType->addValidator(new Validators\LengthValidator(1, 20));
		$itemType->configureValues([
			self::TASK_TYPE,
			self::EPIC_TYPE
		]);

		$parentId = new Fields\IntegerField('PARENT_ID');

		$sort = new Fields\IntegerField('SORT');
		$sort->configureDefaultValue(0);

		$createdBy = new Fields\IntegerField('CREATED_BY');

		$modifiedBy = new Fields\IntegerField('MODIFIED_BY');

		$storyPoints = new Fields\StringField('STORY_POINTS');

		$sourceId = new Fields\IntegerField('SOURCE_ID');

		$info = new Fields\ObjectField('INFO');
		$info->configureRequired(false);
		$info->configureObjectClass(ItemInfoColumn::class);
		$info->configureSerializeCallback(function (?ItemInfoColumn $itemInfoColumn)
		{
			return $itemInfoColumn ? json_encode($itemInfoColumn->getInfoData()) : [];
		});
		$info->configureUnserializeCallback(function ($value)
		{
			$data = (is_string($value) && !empty($value) ? json_decode($value, true) : []);

			$itemInfoColumn = new ItemInfoColumn();
			$itemInfoColumn->setInfoData($data);

			return $itemInfoColumn;
		});

		$entity = new Reference('ENTITY', EntityTable::class, Join::on('this.ENTITY_ID', 'ref.ID'));
		$entity->configureJoinType(Join::TYPE_LEFT);

		return [
			$id,
			$entityId,
			$typeId,
			$active,
			$name,
			$description,
			$itemType,
			$parentId,
			$sort,
			$createdBy,
			$modifiedBy,
			$storyPoints,
			$sourceId,
			$info,
			$entity,
		];
	}

	/**
	 * Deletes an item by entity id.
	 *
	 * @param int $entityId Entity id.
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function deleteByEntityId(int $entityId): void
	{
		$connection = Application::getConnection();
		$connection->queryExecute('DELETE FROM ' . self::getTableName() . ' WHERE ENTITY_ID = ' . (int)$entityId);
	}

	/**
	 * Deletes an item by source id.
	 *
	 * @param int $sourceId Source id.
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function deleteBySourceId(int $sourceId): void
	{
		$connection = Application::getConnection();
		$connection->queryExecute('DELETE FROM ' . self::getTableName() . ' WHERE SOURCE_ID = ' . (int)$sourceId);
	}

	/**
	 * Activates an item by source id.
	 *
	 * @param int $sourceId Source id.
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function activateBySourceId(int $sourceId): void
	{
		$connection = Application::getConnection();
		$connection->queryExecute(
			'UPDATE ' . self::getTableName() . ' SET ACTIVE = \'Y\' WHERE SOURCE_ID = ' . (int)$sourceId
		);

		self::sendAddItemEvent($sourceId);
	}

	/**
	 * Deactivates an item by source id.
	 *
	 * @param int $sourceId Source id.
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function deactivateBySourceId(int $sourceId): void
	{
		self::sendRemoveItemEvent($sourceId);

		$connection = Application::getConnection();
		$connection->queryExecute(
			'UPDATE ' . self::getTableName() . ' SET ACTIVE = \'N\' WHERE SOURCE_ID = ' . (int)$sourceId
		);
	}

	/**
	 * Returns a list of fields to update a item.
	 *
	 * @return array
	 */
	public function getFieldsToUpdateItem(): array
	{
		$fields = [];

		if ($this->name)
		{
			$fields['NAME'] = $this->name;
		}

		if ($this->description)
		{
			$fields['DESCRIPTION'] = $this->description;
		}

		if ($this->entityId)
		{
			$fields['ENTITY_ID'] = $this->entityId;
		}

		if ($this->typeId)
		{
			$fields['TYPE_ID'] = $this->typeId;
		}

		if ($this->itemType)
		{
			$fields['ITEM_TYPE'] = $this->itemType;
		}

		if ($this->parentId !== null)
		{
			$fields['PARENT_ID'] = $this->parentId;
		}

		if ($this->sort !== null)
		{
			$fields['SORT'] = $this->sort;
		}

		if ($this->createdBy)
		{
			$fields['CREATED_BY'] = $this->createdBy;
		}

		if ($this->modifiedBy)
		{
			$fields['MODIFIED_BY'] = $this->modifiedBy;
		}

		if ($this->storyPoints !== null)
		{
			$fields['STORY_POINTS'] = $this->storyPoints;
		}

		if ($this->info)
		{
			$fields['INFO'] = $this->info;
		}

		return $fields;
	}

	/**
	 * Returns a list of fields to create a task item.
	 *
	 * @return array
	 * @throws ArgumentNullException
	 */
	public function getFieldsToCreateTaskItem(): array
	{
		$this->checkRequiredParametersToCreateTaskItem();

		return [
			'ENTITY_ID' => $this->entityId,
			'ITEM_TYPE' => self::TASK_TYPE,
			'PARENT_ID' => $this->parentId,
			'SORT' => $this->getSort(),
			'CREATED_BY' => $this->createdBy,
			'MODIFIED_BY' => $this->createdBy,
			'STORY_POINTS' => $this->storyPoints,
			'SOURCE_ID' => $this->sourceId,
		];
	}

	/**
	 * Returns a list of fields to create a epic item.
	 *
	 * @return array
	 * @throws ArgumentNullException
	 */
	public function getFieldsToCreateEpicItem(): array
	{
		$this->checkRequiredParametersToCreateEpicItem();

		return [
			'NAME' => $this->name,
			'DESCRIPTION' => $this->description,
			'ENTITY_ID' => $this->entityId,
			'ITEM_TYPE' => self::EPIC_TYPE,
			'PARENT_ID' => $this->parentId,
			'SORT' => $this->sort,
			'CREATED_BY' => $this->createdBy,
			'MODIFIED_BY' => $this->createdBy,
			'STORY_POINTS' => $this->storyPoints,
			'INFO' => $this->info,
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

	public function getId()
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = (int) $id;
	}

	public function getEntityId(): int
	{
		return ($this->entityId ? $this->entityId : 0);
	}

	public function setEntityId(int $entityId): void
	{
		$this->entityId = (int) $entityId;
	}

	public function getTypeId(): int
	{
		return ($this->typeId ? $this->typeId : 0);
	}

	public function setTypeId(int $typeId): void
	{
		$this->typeId = (int) $typeId;
	}

	public function getActive(): string
	{
		return ($this->active ? $this->active : 'Y');
	}

	public function setActive(string $active): void
	{
		$this->active = $active;
	}

	public function getName(): string
	{
		return ($this->name ? $this->name : '');
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function getDescription(): string
	{
		return ($this->description ? $this->description : '');
	}

	public function setDescription(string $description): void
	{
		$this->description = $description;
	}

	public function getParentId(): int
	{
		return ($this->parentId ? $this->parentId : 0);
	}

	public function setParentId(int $parentId): void
	{
		$this->parentId = (int) $parentId;
	}

	public function getSort(): int
	{
		return ($this->sort ? $this->sort : 1);
	}

	public function setSort(int $sort): void
	{
		$this->sort = (int) $sort;
	}

	public function getCreatedBy(): int
	{
		return ($this->createdBy ? $this->createdBy : 0);
	}

	public function setCreatedBy(int $createdBy): void
	{
		$this->createdBy = (int) $createdBy;
	}

	public function setModifiedBy(int $modifiedBy): void
	{
		$this->modifiedBy = (int) $modifiedBy;
	}

	public function getStoryPoints(): string
	{
		return ($this->storyPoints <> '' ? $this->storyPoints : '');
	}

	public function setStoryPoints(string $storyPoints): void
	{
		$this->storyPoints = $storyPoints;
	}

	public function getSourceId()
	{
		return $this->sourceId;
	}

	public function setSourceId(int $sourceId): void
	{
		$this->sourceId = (int) $sourceId;
	}

	public function getItemType()
	{
		return $this->itemType;
	}

	public function setItemType(string $itemType): void
	{
		$this->itemType = $itemType;
	}

	public function getInfo(): ItemInfoColumn
	{
		return ($this->info ? $this->info : new ItemInfoColumn());
	}

	public function setInfo(ItemInfoColumn $info): void
	{
		$this->info = $info;
	}

	public function getTmpId(): string
	{
		return $this->tmpId;
	}

	public function setTmpId(string $tmpId): void
	{
		$this->tmpId = $tmpId;
	}

	/**
	 * @throws ArgumentNullException
	 */
	private function checkRequiredParametersToCreateTaskItem(): void
	{
		if (empty($this->entityId))
		{
			throw new ArgumentNullException('ENTITY_ID');
		}

		if (empty($this->createdBy))
		{
			throw new ArgumentNullException('CREATED_BY');
		}

		if (empty($this->sourceId))
		{
			throw new ArgumentNullException('SOURCE_ID');
		}
	}

	/**
	 * @throws ArgumentNullException
	 */
	private function checkRequiredParametersToCreateEpicItem(): void
	{
		if (empty($this->name))
		{
			throw new ArgumentNullException('NAME');
		}

		if (empty($this->entityId))
		{
			throw new ArgumentNullException('ENTITY_ID');
		}

		if (empty($this->createdBy))
		{
			throw new ArgumentNullException('CREATED_BY');
		}

		if (empty($this->info))
		{
			throw new ArgumentNullException('INFO');
		}
	}

	private static function fillItemObjectByData(ItemTable $item, array $itemData): ItemTable
	{
		if ($itemData['ID'])
		{
			$item->setId($itemData['ID']);
		}
		if ($itemData['ENTITY_ID'])
		{
			$item->setEntityId($itemData['ENTITY_ID']);
		}
		if ($itemData['TYPE_ID'])
		{
			$item->setTypeId($itemData['TYPE_ID']);
		}
		if ($itemData['ACTIVE'])
		{
			$item->setActive($itemData['ACTIVE']);
		}
		if ($itemData['NAME'])
		{
			$item->setName($itemData['NAME']);
		}
		if ($itemData['DESCRIPTION'])
		{
			$item->setDescription($itemData['DESCRIPTION']);
		}
		if ($itemData['PARENT_ID'])
		{
			$item->setParentId($itemData['PARENT_ID']);
		}
		if ($itemData['SORT'])
		{
			$item->setSort($itemData['SORT']);
		}
		if ($itemData['CREATED_BY'])
		{
			$item->setCreatedBy($itemData['CREATED_BY']);
		}
		if ($itemData['MODIFIED_BY'])
		{
			$item->setModifiedBy($itemData['MODIFIED_BY']);
		}
		if ($itemData['STORY_POINTS'] <> '')
		{
			$item->setStoryPoints($itemData['STORY_POINTS']);
		}
		if ($itemData['SOURCE_ID'])
		{
			$item->setSourceId($itemData['SOURCE_ID']);
		}
		if ($itemData['ITEM_TYPE'])
		{
			$item->setItemType($itemData['ITEM_TYPE']);
		}
		if ($itemData['INFO'])
		{
			$item->setInfo($itemData['INFO']);
		}
		return $item;
	}

	private static function sendAddItemEvent(int $sourceId): void
	{
		$pushService = (Loader::includeModule('pull') ? new PushService() : null);

		if ($pushService)
		{
			$itemService = new ItemService();
			$item = $itemService->getItemBySourceId($sourceId);

			$pushService->sendAddItemEvent($item);
		}
	}

	private static function sendRemoveItemEvent(int $sourceId): void
	{
		$pushService = (Loader::includeModule('pull') ? new PushService() : null);

		if ($pushService)
		{
			$itemService = new ItemService();
			$item = $itemService->getItemBySourceId($sourceId);

			$pushService->sendRemoveItemEvent($item);
		}
	}
}
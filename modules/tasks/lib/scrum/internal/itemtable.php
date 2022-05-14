<?php
namespace Bitrix\Tasks\Scrum\Internal;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Scrum\Form\ItemInfo;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Scrum\Service\PushService;

/**
 * Class ItemTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Item_Query query()
 * @method static EO_Item_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Item_Result getById($id)
 * @method static EO_Item_Result getList(array $parameters = [])
 * @method static EO_Item_Entity getEntity()
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Item createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Item_Collection createCollection()
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Item wakeUpObject($row)
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Item_Collection wakeUpCollection($rows)
 */
class ItemTable extends Entity\DataManager
{
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

		$epicId = new Fields\IntegerField('EPIC_ID');

		$active = new Fields\StringField('ACTIVE');
		$active->addValidator(new Validators\LengthValidator(1, 1));
		$active->configureDefaultValue('Y');

		$name = new Fields\StringField('NAME');
		$name->addValidator(new Validators\LengthValidator(null, 255));

		$description = new Fields\TextField('DESCRIPTION');

		$sort = new Fields\IntegerField('SORT');
		$sort->configureDefaultValue(0);

		$createdBy = new Fields\IntegerField('CREATED_BY');

		$modifiedBy = new Fields\IntegerField('MODIFIED_BY');

		$storyPoints = new Fields\StringField('STORY_POINTS');

		$sourceId = new Fields\IntegerField('SOURCE_ID');

		$info = new Fields\ObjectField('INFO');
		$info->configureRequired(false);
		$info->configureObjectClass(ItemInfo::class);
		$info->configureSerializeCallback(function (?ItemInfo $itemInfo)
		{
			return $itemInfo ? Json::encode($itemInfo->getInfoData()) : [];
		});
		$info->configureUnserializeCallback(function ($value)
		{
			$data = (is_string($value) && !empty($value) ? Json::decode($value) : []);

			$itemInfo = new ItemInfo();
			$itemInfo->setInfoData($data);

			return $itemInfo;
		});

		$entity = new Reference('ENTITY', EntityTable::class, Join::on('this.ENTITY_ID', 'ref.ID'));
		$entity->configureJoinType(Join::TYPE_LEFT);

		$type = new Reference('TYPE', TypeTable::class, Join::on('this.TYPE_ID', 'ref.ID'));
		$type->configureJoinType(Join::TYPE_LEFT);

		$epic = new Reference('EPIC', EpicTable::class, Join::on('this.EPIC_ID', 'ref.ID'));
		$epic->configureJoinType(Join::TYPE_LEFT);

		return [
			$id,
			$entityId,
			$typeId,
			$epicId,
			$active,
			$name,
			$description,
			$sort,
			$createdBy,
			$modifiedBy,
			$storyPoints,
			$sourceId,
			$info,
			$entity,
			$type,
			$epic,
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
			'UPDATE ' . self::getTableName() . ' SET ACTIVE = \'N\' WHERE SOURCE_ID = ' . (int) $sourceId
		);
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
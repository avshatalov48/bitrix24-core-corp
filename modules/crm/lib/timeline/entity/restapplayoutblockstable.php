<?php

namespace Bitrix\Crm\Timeline\Entity;

use Bitrix\Main\DB\Connection;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Query\Query;

/**
 * Class TimelineRestAppLayoutBlocksTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ITEM_TYPE int mandatory
 * <li> ITEM_ID int mandatory
 * <li> CLIENT_ID string(128) mandatory
 * <li> BLOCKS text optional
 * </ul>
 *
 * @package Bitrix\Crm
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RestAppLayoutBlocks_Query query()
 * @method static EO_RestAppLayoutBlocks_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_RestAppLayoutBlocks_Result getById($id)
 * @method static EO_RestAppLayoutBlocks_Result getList(array $parameters = [])
 * @method static EO_RestAppLayoutBlocks_Entity getEntity()
 * @method static \Bitrix\Crm\Timeline\Entity\EO_RestAppLayoutBlocks createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Timeline\Entity\EO_RestAppLayoutBlocks_Collection createCollection()
 * @method static \Bitrix\Crm\Timeline\Entity\EO_RestAppLayoutBlocks wakeUpObject($row)
 * @method static \Bitrix\Crm\Timeline\Entity\EO_RestAppLayoutBlocks_Collection wakeUpCollection($rows)
 */

class RestAppLayoutBlocksTable extends DataManager
{
	public const ACTIVITY_ITEM_TYPE = 1;
	public const TIMELINE_ITEM_TYPE = 2;
	public const SUSPENDED_ACTIVITY_TYPE = 3;

	private const ALLOWED_ITEM_TYPES = [
		self::ACTIVITY_ITEM_TYPE,
		self::TIMELINE_ITEM_TYPE,
		self::SUSPENDED_ACTIVITY_TYPE,
	];

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_timeline_rest_app_layout_blocks';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		$fieldsMap = [];

		$fieldsMap[] = (new IntegerField('ID'))
			->configurePrimary()
			->configureAutocomplete()
		;
		$fieldsMap[] = (new IntegerField('ITEM_TYPE'))
			->configureRequired()
			->addValidator(static fn($value) => in_array($value, self::ALLOWED_ITEM_TYPES, true))
		;
		$fieldsMap[] = (new IntegerField('ITEM_ID'))
			->configureRequired()
		;
		$fieldsMap[] = (new StringField('CLIENT_ID'))
			->configureRequired()
			->addValidator(new LengthValidator(null, 128))
		;
		$fieldsMap[] = (new TextField('LAYOUT'));

		return $fieldsMap;
	}

	public static function deleteByIds(array $ids): void
	{
		if (empty($ids))
		{
			return;
		}

		if (count($ids) === 1)
		{
			static::delete(...array_values($ids));

			return;
		}

		$ids = array_map(static fn(mixed $id) => (int)$id, $ids);

		$idsFilter = Query::buildFilterSql(static::getEntity(), [ '@ID' => $ids ]);
		$dbName = static::getConnection()->getSqlHelper()->quote(static::getEntity()->getDBTableName());

		static::getConnection()->query("
			DELETE FROM {$dbName}
			WHERE {$idsFilter}
		");

		static::cleanCache();
	}

	protected static function getConnection(): Connection
	{
		return static::getEntity()->getConnection();
	}
}

<?php
namespace Bitrix\BIConnector;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

Loc::loadMessages(__FILE__);

/**
 * Class DictStructureAggTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_DictStructureAgg_Query query()
 * @method static EO_DictStructureAgg_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_DictStructureAgg_Result getById($id)
 * @method static EO_DictStructureAgg_Result getList(array $parameters = [])
 * @method static EO_DictStructureAgg_Entity getEntity()
 * @method static \Bitrix\BIConnector\EO_DictStructureAgg createObject($setDefaultValues = true)
 * @method static \Bitrix\BIConnector\EO_DictStructureAgg_Collection createCollection()
 * @method static \Bitrix\BIConnector\EO_DictStructureAgg wakeUpObject($row)
 * @method static \Bitrix\BIConnector\EO_DictStructureAgg_Collection wakeUpCollection($rows)
 */
class DictStructureAggTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_biconnector_dict_structure_agg';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new IntegerField(
				'DEP_ID',
				[
					'primary' => true
				]
			),
			new StringField(
				'DEP_NAME',
			),
			new StringField(
				'DEP_IDS',
			),
			new StringField(
				'DEP_NAMES',
			),
			new StringField(
				'DEP_NAME_IDS',
			),
		];
	}

	/**
	 * Deletes cached data by filter.
	 *
	 * @param array $filter Delete filter.
	 *
	 * @return void
	 */
	public static function deleteByFilter(array $filter)
	{
		$entity = static::getEntity();
		$sqlTableName = static::getTableName();

		$where = \Bitrix\Main\ORM\Query\Query::buildFilterSql($entity, $filter);
		if ($where)
		{
			$sql = 'DELETE FROM ' . $sqlTableName . ' WHERE ' . $where;
			$manager = Manager::getInstance();
			$manager->getDatabaseConnection()->queryExecute($sql);
		}
	}

	/**
	 * Batch insert.
	 *
	 * @param string $select Sql query.
	 *
	 * @return void
	 */
	public static function insertSelect($select)
	{
		$sqlTableName = static::getTableName();

		$sql = 'INSERT INTO ' . $sqlTableName . ' ' . $select;
		$manager = Manager::getInstance();
		$manager->getDatabaseConnection()->queryExecute($sql);
	}
}

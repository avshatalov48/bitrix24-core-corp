<?php
namespace Bitrix\BIConnector;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

Loc::loadMessages(__FILE__);

/**
 * Class DictionaryDataTable
 *
 * Fields:
 * <ul>
 * <li> DICTIONARY_ID int mandatory
 * <li> VALUE_ID int mandatory
 * <li> VALUE_STR string(500) optional
 * </ul>
 *
 * @package Bitrix\BIConnector
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_DictionaryData_Query query()
 * @method static EO_DictionaryData_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_DictionaryData_Result getById($id)
 * @method static EO_DictionaryData_Result getList(array $parameters = [])
 * @method static EO_DictionaryData_Entity getEntity()
 * @method static \Bitrix\BIConnector\EO_DictionaryData createObject($setDefaultValues = true)
 * @method static \Bitrix\BIConnector\EO_DictionaryData_Collection createCollection()
 * @method static \Bitrix\BIConnector\EO_DictionaryData wakeUpObject($row)
 * @method static \Bitrix\BIConnector\EO_DictionaryData_Collection wakeUpCollection($rows)
 */

class DictionaryDataTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_biconnector_dictionary_data';
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
				'DICTIONARY_ID',
				[
					'primary' => true,
					'title' => Loc::getMessage('DICTIONARY_DATA_ENTITY_DICTIONARY_ID_FIELD')
				]
			),
			new IntegerField(
				'VALUE_ID',
				[
					'primary' => true,
					'title' => Loc::getMessage('DICTIONARY_DATA_ENTITY_VALUE_ID_FIELD')
				]
			),
			new StringField(
				'VALUE_STR',
				[
					'validation' => [__CLASS__, 'validateValueStr'],
					'title' => Loc::getMessage('DICTIONARY_DATA_ENTITY_VALUE_STR_FIELD')
				]
			),
		];
	}

	/**
	 * Returns validators for VALUE_STR field.
	 *
	 * @return array
	 */
	public static function validateValueStr()
	{
		return [
			new LengthValidator(null, 500),
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

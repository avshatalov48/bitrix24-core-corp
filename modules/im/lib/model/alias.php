<?php
namespace Bitrix\Im\Model;

use Bitrix\Im\Internals\Query;
use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

/**
 * Class AliasTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ALIAS string(255) mandatory
 * <li> ENTITY_TYPE string(255) mandatory
 * <li> ENTITY_ID string(255) mandatory
 * </ul>
 *
 * @package Bitrix\Im
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Alias_Query query()
 * @method static EO_Alias_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Alias_Result getById($id)
 * @method static EO_Alias_Result getList(array $parameters = array())
 * @method static EO_Alias_Entity getEntity()
 * @method static \Bitrix\Im\Model\EO_Alias createObject($setDefaultValues = true)
 * @method static \Bitrix\Im\Model\EO_Alias_Collection createCollection()
 * @method static \Bitrix\Im\Model\EO_Alias wakeUpObject($row)
 * @method static \Bitrix\Im\Model\EO_Alias_Collection wakeUpCollection($rows)
 */

class AliasTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_im_alias';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('ALIAS_ENTITY_ID_FIELD'),
			),
			'ALIAS' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateAlias'),
				'title' => Loc::getMessage('ALIAS_ENTITY_ALIAS_FIELD'),
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
				'required' => true,
				'title' => Loc::getMessage('ALIAS_ENTITY_ENTITY_DATE_CREATE_FIELD'),
			),
			'ENTITY_TYPE' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateEntityType'),
				'title' => Loc::getMessage('ALIAS_ENTITY_ENTITY_TYPE_FIELD'),
			),
			'ENTITY_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateEntityId'),
				'title' => Loc::getMessage('ALIAS_ENTITY_ENTITY_ID_FIELD'),
			),
		);
	}
	/**
	 * Returns validators for ALIAS field.
	 *
	 * @return array
	 */
	public static function validateAlias()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for ENTITY_TYPE field.
	 *
	 * @return array
	 */
	public static function validateEntityType()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for ENTITY_ID field.
	 *
	 * @return array
	 */
	public static function validateEntityId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	public static function deleteBatch(array $filter, $limit = 0)
	{
		$tableName = static::getTableName();
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$query = new Query(static::getEntity());
		$query->setFilter($filter);
		$query->getQuery();

		$alias = $sqlHelper->quote($query->getInitAlias()) . '.';
		$where = str_replace($alias, '', $query->getWhere());

		$sql = 'DELETE FROM ' . $tableName . ' WHERE ' . $where;
		if($limit > 0)
		{
			$sql .= ' LIMIT ' . $limit;
		}

		$connection->queryExecute($sql);
		return $connection->getAffectedRowsCount();
	}
}
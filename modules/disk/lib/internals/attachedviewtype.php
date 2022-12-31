<?php

namespace Bitrix\Disk\Internals;

use Bitrix\Main;
use Bitrix\Main\NotImplementedException;

/**
 * Class UfGalleryType
 *
 * Fields:
 * <ul>
 * <li> ENTITY_TYPE string(100) mandatory
 * <li> ENTITY_ID int mandatory
 * <li> VALUE string(20) optional
 * </ul>
 *
 * @package Bitrix\Disk
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_AttachedViewType_Query query()
 * @method static EO_AttachedViewType_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_AttachedViewType_Result getById($id)
 * @method static EO_AttachedViewType_Result getList(array $parameters = [])
 * @method static EO_AttachedViewType_Entity getEntity()
 * @method static \Bitrix\Disk\Internals\EO_AttachedViewType createObject($setDefaultValues = true)
 * @method static \Bitrix\Disk\Internals\EO_AttachedViewType_Collection createCollection()
 * @method static \Bitrix\Disk\Internals\EO_AttachedViewType wakeUpObject($row)
 * @method static \Bitrix\Disk\Internals\EO_AttachedViewType_Collection wakeUpCollection($rows)
 */

final class AttachedViewTypeTable extends DataManager
{
	/**
	 * Returns DB table name for entity
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_disk_attached_view_type';
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ENTITY_TYPE' => array(
				'data_type' => 'string',
				'primary' => true
			),
			'ENTITY_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'VALUE' => array(
				'data_type' => 'string',
			)
		);
	}

	public static function set(array $params = [])
	{
		$entityType = (!empty($params['ENTITY_TYPE']) ? $params['ENTITY_TYPE'] : '');
		$entityId = (!empty($params['ENTITY_ID']) ? intval($params['ENTITY_ID']) : 0);
		$value = (isset($params['VALUE']) ? $params['VALUE'] : '');

		if (empty($entityType))
		{
			throw new Main\SystemException("Empty ENTITY_TYPE.");
		}

		if ($entityId <= 0)
		{
			throw new Main\SystemException("Empty ENTITY_ID.");
		}

		if ($value == '')
		{
			throw new Main\SystemException("Empty VALUE.");
		}

		$connection = Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		$insertFields = [
			'ENTITY_TYPE' => $helper->forSql($entityType),
			'ENTITY_ID' => $entityId,
			'VALUE' => $helper->forSql($value),
		];

		$updateFields = [
			'VALUE' => $helper->forSql($value),
		];

		$merge = $helper->prepareMerge(
			static::getTableName(),
			[ 'ENTITY_TYPE', 'ENTITY_ID' ],
			$insertFields,
			$updateFields
		);

		if ($merge[0] != '')
		{
			$connection->query($merge[0]);
		}
	}

	public static function add(array $data)
	{
		throw new NotImplementedException("Use set() method of the class.");
	}

	public static function update($primary, array $data)
	{
		throw new NotImplementedException("Use set() method of the class.");
	}
}

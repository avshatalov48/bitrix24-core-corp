<?php
namespace Bitrix\Disk\Internals;

use Bitrix\Main\Entity;

/**
 * Class StorageTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> NAME string(100) optional
 * <li> CODE string(32) optional
 * <li> XML_ID string(50) optional
 * <li> MODULE_ID string(32) mandatory
 * <li> ENTITY_TYPE string(100) mandatory
 * <li> ENTITY_ID string(12) mandatory
 * <li> ENTITY_MISC_DATA text optional
 * <li> ROOT_OBJECT_ID int
 * <li> USE_INTERNAL_RIGHTS int
 * <li> SITE_ID int
 * </ul>
 *
 * @package Bitrix\Disk
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Storage_Query query()
 * @method static EO_Storage_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Storage_Result getById($id)
 * @method static EO_Storage_Result getList(array $parameters = [])
 * @method static EO_Storage_Entity getEntity()
 * @method static \Bitrix\Disk\Internals\EO_Storage createObject($setDefaultValues = true)
 * @method static \Bitrix\Disk\Internals\EO_Storage_Collection createCollection()
 * @method static \Bitrix\Disk\Internals\EO_Storage wakeUpObject($row)
 * @method static \Bitrix\Disk\Internals\EO_Storage_Collection wakeUpCollection($rows)
 */

final class StorageTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_disk_storage';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'NAME' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateName'),
			),
			'CODE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCode'),
			),
			'XML_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateXmlId'),
			),
			'MODULE_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateModule'),
			),
			'ENTITY_TYPE' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateEntityType'),
			),
			'ENTITY_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateEntityId'),
			),
			'ENTITY_MISC_DATA' => array(
				'data_type' => 'text',
			),
			'ROOT_OBJECT_ID' => array(
				'data_type' => 'integer',
			),
			'ROOT_OBJECT' => array(
				'data_type' => '\Bitrix\Disk\Internals\ObjectTable',
				'reference' => array(
					'=this.ROOT_OBJECT_ID' => 'ref.ID'
				),
				'join_type' => 'INNER',
			),
			'USE_INTERNAL_RIGHTS' => array(
				'data_type' => 'boolean',
				'values' => array(0, 1),
				'default_value' => 1,
			),
			'SITE_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateSiteId'),
			),
		);
	}

	public static function validateCode()
	{
		return array(
			new Entity\Validator\Length(null, 32),
		);
	}

	public static function validateXmlId()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}

	public static function validateName()
	{
		return array(
			new Entity\Validator\Length(null, 100),
		);
	}

	public static function validateModule()
	{
		return array(
			new Entity\Validator\Length(1, 32),
			new Entity\Validator\RegExp('/^[a-zA-Z0-9_\-.]+$/'),
		);
	}

	public static function validateEntityType()
	{
		return array(
			new Entity\Validator\Length(1, 100),
		);
	}

	public static function validateEntityId()
	{
		return array(
			new Entity\Validator\Length(1, 32),
			new Entity\Validator\RegExp('/^[a-zA-Z0-9_-]+$/'),
		);
	}

	public static function validateSiteId()
	{
		return array(
			new Entity\Validator\Length(null, 2),
		);
	}
}

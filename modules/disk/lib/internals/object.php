<?php
namespace Bitrix\Disk\Internals;

use Bitrix\Disk\TypeFile;
use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\Result;
use Bitrix\Main\Entity\UpdateResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\Viewer\FilePreviewTable;

Loc::loadMessages(__FILE__);

/**
 * Class ObjectTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> NAME string(255) optional
 * <li> TYPE int optional
 * <li> CODE string(50) optional
 * <li> XML_ID string(50) optional
 * <li> STORAGE_ID int mandatory
 * <li> REAL_OBJECT_ID int optional
 * <li> PARENT_ID int optional
 * <li> CONTENT_PROVIDER string optional
 * <li> CREATE_TIME datetime mandatory
 * <li> UPDATE_TIME datetime optional
 * <li> SYNC_UPDATE_TIME datetime optional
 * <li> DELETE_TIME datetime optional
 * <li> CREATED_BY int mandatory
 * <li> UPDATED_BY int optional
 * <li> DELETED_BY int optional
 * <li> GLOBAL_CONTENT_VERSION int optional
 * <li> FILE_ID int optional
 * <li> SIZE int optional
 * <li> EXTERNAL_HASH string(255) optional
 * <li> ETAG string(255) optional
 * <li> DELETED_TYPE int optional
 * <li> TYPE_FILE int optional
 * <li> PREVIEW_ID int optional
 * <li> VIEW_ID int optional
 * <li> SEARCH_INDEX string optional
 * </ul>
 *
 * @package Bitrix\Disk
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Object_Query query()
 * @method static EO_Object_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Object_Result getById($id)
 * @method static EO_Object_Result getList(array $parameters = [])
 * @method static EO_Object_Entity getEntity()
 * @method static \Bitrix\Disk\Internals\EO_Object createObject($setDefaultValues = true)
 * @method static \Bitrix\Disk\Internals\EO_Object_Collection createCollection()
 * @method static \Bitrix\Disk\Internals\EO_Object wakeUpObject($row)
 * @method static \Bitrix\Disk\Internals\EO_Object_Collection wakeUpCollection($rows)
 */

Loc::loadMessages(__FILE__);

class ObjectTable extends DataManager
{
	const TYPE_FOLDER = 2;
	const TYPE_FILE   = 3;

	const DELETED_TYPE_NONE  = 0;
	const DELETED_TYPE_ROOT  = 3;
	const DELETED_TYPE_CHILD = 4;

	const EVENT_ON_BEFORE_MOVE = "OnBeforeMove";
	const EVENT_ON_MOVE        = "OnMove";
	const EVENT_ON_AFTER_MOVE  = "OnAfterMove";

	const EVENT_ON_BEFORE_UPDATE_ATTR_BY_FILTER = "OnBeforeUpdateAttrByFilter";
	const EVENT_ON_UPDATE_ATTR_BY_FILTER        = "OnUpdateAttrByFilter";
	const EVENT_ON_AFTER_UPDATE_ATTR_BY_FILTER  = "OnAfterUpdateAttrByFilter";

	public static function add(array $data)
	{
		if(isset($data['CREATED_BY']) && !isset($data['UPDATED_BY']))
		{
			$data['UPDATED_BY'] = $data['CREATED_BY'];
		}

		return parent::add($data);
	}

	public static function getTableName()
	{
		return 'b_disk_object';
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
				'title' => Loc::getMessage('DISK_OBJECT_ENTITY_NAME_FIELD'),
			),
			'TYPE' => array(
				'data_type' => 'enum',
				'values' => static::getListOfTypeValues(),
			),
			'CODE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCode'),
			),
			'XML_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateXmlId'),
			),
			'STORAGE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'STORAGE' => array(
				'data_type' => '\Bitrix\Disk\Internals\StorageTable',
				'reference' => array(
					'=this.STORAGE_ID' => 'ref.ID'
				),
				'join_type' => 'INNER',
			),
			'REAL_OBJECT_ID' => array(
				'data_type' => 'integer',
			),
			'REAL_OBJECT' => array(
				'data_type' => '\Bitrix\Disk\Internals\ObjectTable',
				'reference' => array(
					'=this.REAL_OBJECT_ID' => 'ref.ID'
				),
				'join_type' => 'INNER',
			),
			'LOCK' => array(
				'data_type' => '\Bitrix\Disk\Internals\ObjectLockTable',
				'reference' => array(
					'=this.REAL_OBJECT_ID' => 'ref.OBJECT_ID'
				)
			),
			'TTL' => array(
				'data_type' => '\Bitrix\Disk\Internals\ObjectTtlTable',
				'reference' => array(
					'=this.ID' => 'ref.OBJECT_ID'
				)
			),
			'PARENT_ID' => array(
				'data_type' => 'integer',
			),
			'CONTENT_PROVIDER' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateContentProvider'),
			),
			'CREATE_TIME' => array(
				'data_type' => 'datetime',
				'required' => true,
				'default_value' => function() {
					return new DateTime();
				},
			),
			'UPDATE_TIME' => array(
				'data_type' => 'datetime',
				'default_value' => function() {
					return new DateTime();
				},
			),
			'SYNC_UPDATE_TIME' => array(
				'data_type' => 'datetime',
				'default_value' => function() {
					return new DateTime();
				},
			),
			'DELETE_TIME' => array(
				'data_type' => 'datetime',
			),
			'CREATED_BY' => array(
				'data_type' => 'integer',
			),
			'CREATE_USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array(
					'=this.CREATED_BY' => 'ref.ID'
				),
			),
			'UPDATED_BY' => array(
				'data_type' => 'integer',
			),
			'UPDATE_USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array(
					'=this.UPDATED_BY' => 'ref.ID'
				),
			),
			'DELETED_BY' => array(
				'data_type' => 'integer',
			),
			'DELETE_USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array(
					'=this.DELETED_BY' => 'ref.ID'
				),
			),
			'GLOBAL_CONTENT_VERSION' => array(
				'data_type' => 'integer',
			),
			'FILE_ID' => array(
				'data_type' => 'integer',
			),
			'FILE_CONTENT' => array(
				'data_type' => Main\FileTable::class,
				'reference' => array(
					'=this.FILE_ID' => 'ref.ID'
				),
				'join_type' => 'LEFT',
			),
			'SIZE' => array(
				'data_type' => 'integer',
			),
			'EXTERNAL_HASH' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateExternalHash'),
			),
			'ETAG' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateEtag'),
			),
			'DELETED_TYPE' => array(
				'data_type' => 'enum',
				'values' => static::getListOfDeletedTypes(),
				'default_value' => self::DELETED_TYPE_NONE,
			),
			'TYPE_FILE' => array(
				'data_type' => 'enum',
				'values' => TypeFile::getListOfValues(),
			),
			'PATH_PARENT' => array(
				'data_type' => '\Bitrix\Disk\Internals\ObjectPathTable',
				'reference' => array(
					'=this.ID' => 'ref.PARENT_ID'
				),
				'join_type' => 'INNER',
			),
			'PATH_CHILD' => array(
				'data_type' => '\Bitrix\Disk\Internals\ObjectPathTable',
				'reference' => array(
					'=this.ID' => 'ref.OBJECT_ID'
				),
				'join_type' => 'INNER',
			),
			'RECENTLY_USED' => array(
				'data_type' => '\Bitrix\Disk\Internals\RecentlyUsedTable',
				'reference' => array(
					'=this.ID' => 'ref.OBJECT_ID'
				),
				'join_type' => 'INNER',
			),
			'PREVIEW' => array(
				'data_type' => FilePreviewTable::class,
				'reference' => array(
					'=this.FILE_ID' => 'ref.FILE_ID'
				),
				'join_type' => 'LEFT',
			),
			'PREVIEW_ID' => array(
				'data_type' => 'integer',
			),
			'VIEW_ID' => array(
				'data_type' => 'integer',
			),
			'SEARCH_INDEX' => array(
				'data_type' => 'string',
				'expression'  => [
					'%%TABLE_ALIAS.SEARCH_INDEX'
				],
			),
			'HEAD_INDEX' => array(
				'data_type' => '\Bitrix\Disk\Internals\Index\ObjectHeadIndexTable',
				'reference' => array(
					'=this.ID' => 'ref.OBJECT_ID'
				),
				'join_type' => 'INNER',
			),
			'EXTENDED_INDEX' => array(
				'data_type' => '\Bitrix\Disk\Internals\Index\ObjectExtendedIndexTable',
				'reference' => array(
					'=this.ID' => 'ref.OBJECT_ID'
				),
				'join_type' => 'INNER',
			),
			'HAS_SEARCH_INDEX' => array(
				'data_type' => 'boolean',
				'expression'  => [
					'CASE WHEN %%TABLE_ALIAS.SEARCH_INDEX IS NOT NULL THEN TRUE ELSE FALSE END'
				],
			),
			'TRACKED_OBJECT' => array(
				'data_type' => '\Bitrix\Disk\Internals\TrackedObjectTable',
				'reference' => array(
					'=this.ID' => 'ref.OBJECT_ID'
				),
				'join_type' => 'INNER',
			),
		);
	}

	public static function getListOfTypeValues()
	{
		return array(self::TYPE_FILE, self::TYPE_FOLDER);
	}

	public static function getListOfDeletedTypes()
	{
		return array(self::DELETED_TYPE_NONE, self::DELETED_TYPE_CHILD, self::DELETED_TYPE_ROOT);
	}

	public static function validateName()
	{
		return array(
			new Entity\Validator\Length(null, 255),
			new CallableValidator(function($value, $primary, array $row, Entity\Field $field){
				if($value && !Path::validateFilename($value))
				{
					return Loc::getMessage(
						"DISK_OBJECT_ENTITY_ERROR_FIELD_NAME_HAS_INVALID_CHARS",
						array("#FIELD#" => $field->getTitle())
					);
				}

				return true;
			}),
		);
	}

	public static function validateCode()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}

	public static function validateXmlId()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}

	public static function validateExternalHash()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	public static function validateEtag()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	public static function validateContentProvider()
	{
		return array(
			new Entity\Validator\Length(null, 10),
		);
	}

	public static function checkFields(Result $result, $primary, array $data)
	{
		if($result instanceof Entity\UpdateResult)
		{
			if(isset($data['STORAGE_ID']))
			{
				$field = static::getEntity()->getField('STORAGE_ID');
				$result->addError(new Entity\FieldError(
					$field,
					Loc::getMessage("DISK_OBJECT_ENTITY_ERROR_UPDATE_STORAGE_ID", array("#FIELD#" => $field->getTitle()))
				));
			}
			if(isset($data['PARENT_ID']))
			{
				$field = static::getEntity()->getField('PARENT_ID');
				$result->addError(new Entity\FieldError(
					$field,
					Loc::getMessage("DISK_OBJECT_ENTITY_ERROR_UPDATE_PARENT_ID", array("#FIELD#" => $field->getTitle()))
				));
			}
		}

		parent::checkFields($result, $primary, $data);
	}

	public static function onAfterAdd(Entity\Event $event)
	{
		$fields = $event->getParameter('fields');
		$fields['ID'] = $event->getParameter('id');

		if($fields['ID'] && empty($fields['REAL_OBJECT_ID']))
		{
			static::update($fields['ID'], array('REAL_OBJECT_ID' => $fields['ID']));
		}

		if(!empty($fields['PARENT_ID']))
		{
			ObjectPathTable::appendTo($fields['ID'], $fields['PARENT_ID']);
		}
		else
		{
			ObjectPathTable::addAsRoot($fields['ID']);
		}
	}

	public static function onBeforeUpdate(Entity\Event $event)
	{
		$result = new Entity\EventResult();
		/** @var array $data */
		$data = $event->getParameter('fields');
		if(isset($data['UPDATE_TIME']))
		{
			$result->modifyFields(array('SYNC_UPDATE_TIME' => new DateTime()));
		}

		return $result;
	}

	public static function delete($primary)
	{
		$deleteResult = parent::delete($primary);
		if($deleteResult->isSuccess())
		{
			ObjectPathTable::deleteByObject($primary);
		}

		return $deleteResult;
	}

	/**
	 * Move object from node to another node.
	 * Use this method instead update.
	 * @param $primary
	 * @param $newParentId
	 * @return \Bitrix\Main\Entity\UpdateResult
	 */
	public static function move($primary, $newParentId)
	{
		$newParentId = (int)$newParentId;
		// check primary
		static::normalizePrimary($primary);
		static::validatePrimary($primary);

		$data = array(
			'PARENT_ID' => $newParentId,
		);
		$entity = static::getEntity();
		$result = new UpdateResult();

		$event = new Entity\Event($entity, self::EVENT_ON_BEFORE_MOVE, array("id"=>$primary, "fields"=>$data));
		$event->send();
		$event->getErrors($result);
		$data = $event->mergeFields($data);

		// check data
//		static::checkFields($result, $primary, $data);

		if(!$result->isSuccess(true))
		{
			return $result;
		}

		$event = new Entity\Event($entity, self::EVENT_ON_MOVE, array("id"=>$primary, "fields"=>$data));
		$event->send();

		ObjectPathTable::moveTo($primary['ID'], $newParentId);

		// save data
		$connection = Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		$tableName = static::getEntity()->getDBTableName();
		$update = $helper->prepareUpdate($tableName, array('PARENT_ID' => $newParentId));

		$id = array();
		foreach ($primary as $k => $v)
		{
			$id[] = $helper->prepareAssignment($tableName, $k, $v);
		}
		$where = implode(' AND ', $id);

		$sql = "UPDATE ".$tableName." SET ".$update[0]." WHERE ".$where;
		$connection->queryExecute($sql, $update[1]);

		$result = new UpdateResult();
		$result->setAffectedRowsCount($connection);
		$result->setData(array('PARENT_ID' => $newParentId));

		$event = new Entity\Event($entity, self::EVENT_ON_AFTER_MOVE, array("id"=>$primary, "fields"=>$data));
		$event->send();

		return $result;
	}

	public static function updateSyncTime($objectId, DateTime $dateTime)
	{
		$objectId = (int)$objectId;

		$connection = Application::getInstance()->getConnection();
		$table = static::getTableName();
		$tablePath = ObjectPathTable::getTableName();

		$helper = $connection->getSqlHelper();
		$update = $helper->prepareUpdate($table, ['SYNC_UPDATE_TIME' => $dateTime,]);

		$sql = "
			UPDATE {$table} obj
			INNER JOIN {$tablePath} p ON p.OBJECT_ID = obj.ID
			SET {$update[0]}
			WHERE p.PARENT_ID = {$objectId}
		";

		$connection->queryExecute($sql, $update[1]);
	}

	/**
	 * Usage: only if you want to update "unnormalized" attributes (symlink object)
	 * @param array $attributes
	 * @param array $filter
	 * @return \Bitrix\Main\Entity\Result
	 * @internal
	 */
	public static function updateAttributesByFilter(array $attributes, array $filter)
	{
		$entity = static::getEntity();
		$result = new Result();

		$event = new Entity\Event($entity, self::EVENT_ON_BEFORE_UPDATE_ATTR_BY_FILTER, array(
			'fields' => $attributes,
			'filter' => $filter
		));
		$event->send();
		$event->getErrors($result);
		$attributes = $event->mergeFields($attributes);

		//static::checkFields($result, null, $attributes);
		if(!$result->isSuccess(true))
		{
			return $result;
		}

		$event = new Entity\Event($entity, self::EVENT_ON_UPDATE_ATTR_BY_FILTER, array(
			'fields' => $attributes,
			'filter' => $filter
		));
		$event->send();

		$connection = Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		$tableName = static::getEntity()->getDBTableName();
		$update = $helper->prepareUpdate($tableName, $attributes);

		$filterAttributes = array();
		foreach ($filter as $k => $v)
		{
			$filterAttributes[] = $helper->prepareAssignment($tableName, $k, $v);
		}
		$where = implode(' AND ', $filterAttributes);

		$sql = "UPDATE ".$tableName." SET ".$update[0]." WHERE ".$where;
		$connection->queryExecute($sql, $update[1]);

		$event = new Entity\Event($entity, self::EVENT_ON_AFTER_UPDATE_ATTR_BY_FILTER, array(
			'fields' => $attributes,
			'filter' => $filter
		));
		$event->send();

		return $result;
	}

	/**
	 * Get all descendants by this folder.
	 * @param       $objectId
	 * @param array $parameters
	 * @return Main\DB\ArrayResult|Main\DB\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Exception
	 */
	public static function getDescendants($objectId, $parameters = array())
	{
		if(!isset($parameters['filter']))
		{
			$parameters['filter'] = array();
		}
		if(!isset($parameters['select']))
		{
			$parameters['select'] = array('*');
		}

		$parameters['filter']['PATH_CHILD.PARENT_ID'] = $objectId;
		$parameters['filter']['!PATH_CHILD.OBJECT_ID'] = $objectId;

		return static::getList($parameters);
	}

	/**
	 * Get all ancestors by this folder.
	 * @param       $objectId
	 * @param array $parameters
	 * @return Main\DB\ArrayResult|Main\DB\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Exception
	 */
	public static function getAncestors($objectId, $parameters = array())
	{
		if(!isset($parameters['filter']))
		{
			$parameters['filter'] = array();
		}
		if(!isset($parameters['select']))
		{
			$parameters['select'] = array('*');
		}

		$parameters['filter']['PATH_PARENT.OBJECT_ID'] = $objectId;
		$parameters['filter']['!PATH_PARENT.PARENT_ID'] = $objectId;

		return static::getList($parameters);
	}

	/**
	 * Get direct children.
	 * @param       $objectId
	 * @param array $parameters
	 * @return Main\DB\ArrayResult|Main\DB\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Exception
	 */
	public static function getChildren($objectId, $parameters = array())
	{
		if(!isset($parameters['filter']))
		{
			$parameters['filter'] = array();
		}
		if(!isset($parameters['select']))
		{
			$parameters['select'] = array('*');
		}

		$parameters['filter']['PARENT_ID'] = $objectId;

		return static::getList($parameters);
	}

	/**
	 * Get all ancestors by this folder.
	 * @param       $objectId
	 * @param array $parameters
	 * @return Main\DB\ArrayResult|Main\DB\Result
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getParents($objectId, $parameters = array())
	{
		return static::getAncestors($objectId, $parameters);
	}

	/**
	 * @param $primary
	 * @param DateTime $dateTime
	 *
	 * @return Main\ORM\Data\UpdateResult
	 */
	public static function changeSyncDateTime($primary, DateTime $dateTime)
	{
		return static::update($primary, ['SYNC_UPDATE_TIME' => $dateTime]);
	}
}

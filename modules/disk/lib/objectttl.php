<?php


namespace Bitrix\Disk;

use Bitrix\Disk\Internals\Cleaner;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Main\Type\DateTime;

/**
 * Class ObjectTtl
 * @package Bitrix\Disk
 * @method BaseObject getObject()
 */
final class ObjectTtl extends Internals\Entity\Model
{
	/** @var int */
	protected $objectId;
	/** @var DateTime */
	protected $createTime;
	/** @var DateTime */
	protected $deathTime;

	/**
	 * Gets the fully qualified name of table class which belongs to current model.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getTableClassName()
	{
		return Internals\ObjectTtlTable::className();
	}

	/**
	 * Adds row to entity table, fills error collection and builds model.
	 * @param array           $data Data.
	 * @param ErrorCollection $errorCollection Error collection.
	 * @return \Bitrix\Disk\Internals\Model|static|null
	 * @throws \Bitrix\Main\NotImplementedException
	 * @internal
	 */
	public static function add(array $data, ErrorCollection $errorCollection)
	{
		/** @var ObjectTtl $model */
		$model = parent::add($data, $errorCollection);
		if ($model)
		{
			static::addAgentIfNotExist();
		}

		return $model;
	}

	private static function addAgentIfNotExist()
	{
		\CAgent::addAgent(
			Cleaner::className() . '::deleteByTtl(' . Cleaner::DELETE_TYPE_TIME . ', 2);',
			'disk',
			'N',
			3600
		);
	}

	/**
	 * Loads ttl model for object.
	 * @param int $objectId Object id.
	 * @return static
	 */
	public static function loadByObjectId($objectId)
	{
		return static::load(array(
			'OBJECT_ID' => $objectId,
		));
	}

	/**
	 * Returns create time.
	 *
	 * @return DateTime
	 */
	public function getCreateTime()
	{
		return $this->createTime;
	}

	/**
	 * Returns death time.
	 *
	 * @return DateTime
	 */
	public function getDeathTime()
	{
		return $this->deathTime;
	}

	/**
	 * Changes time to death.
	 * @param DateTime $dateTime Time to death.
	 * @return bool
	 */
	public function changeDeathTime(DateTime $dateTime)
	{
		return $this->update(array(
			'DEATH_TIME' => $dateTime,
		));
	}

	/**
	 * Returns object id.
	 *
	 * @return int
	 */
	public function getObjectId()
	{
		return $this->objectId;
	}

	/**
	 * Tells if external link is ready to death.
	 * @return bool
	 */
	public function isReadyToDeath()
	{
		$now = new DateTime;

		return $this->deathTime && $now->getTimestamp() > $this->deathTime->getTimestamp();
	}

	/**
	 * Deletes object which has ttl.
	 *
	 * @param $deletedBy
	 * @return void
	 */
	public function deleteObject($deletedBy)
	{
		$baseObject = $this->getObject();
		if ($baseObject)
		{
			if ($baseObject instanceof Folder)
			{
				$baseObject->deleteTree($deletedBy);
			}
			elseif ($baseObject instanceof File)
			{
				$baseObject->delete($deletedBy);
			}
		}
	}

	/**
	 * Deletes model.
	 * @param int $deletedBy User id.
	 *
	 * @return bool
	 */
	public function delete($deletedBy)
	{
		return $this->deleteInternal();
	}

	/**
	 * Returns the list of pair for mapping data and object properties.
	 * Key is field in DataManager, value is object property.
	 *
	 * @return array
	 */
	public static function getMapAttributes()
	{
		return array(
			'ID' => 'id',
			'OBJECT_ID' => 'objectId',
			'CREATE_TIME' => 'createTime',
			'DEATH_TIME' => 'deathTime',
		);
	}

	/**
	 * Returns the list attributes which is connected with another models.
	 *
	 * @return array
	 */
	public static function getMapReferenceAttributes()
	{
		return array(
			'object' => array(
				'class' => BaseObject::className(),
				'load' => function(ObjectTtl $objectTtl){
					return BaseObject::loadById($objectTtl->getObjectId());
				},
			),
		);
	}
}
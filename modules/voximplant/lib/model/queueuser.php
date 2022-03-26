<?php

namespace Bitrix\Voximplant\Model;

use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\ArgumentException;

/**
 * Class QueueUserTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_QueueUser_Query query()
 * @method static EO_QueueUser_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_QueueUser_Result getById($id)
 * @method static EO_QueueUser_Result getList(array $parameters = array())
 * @method static EO_QueueUser_Entity getEntity()
 * @method static \Bitrix\Voximplant\Model\EO_QueueUser createObject($setDefaultValues = true)
 * @method static \Bitrix\Voximplant\Model\EO_QueueUser_Collection createCollection()
 * @method static \Bitrix\Voximplant\Model\EO_QueueUser wakeUpObject($row)
 * @method static \Bitrix\Voximplant\Model\EO_QueueUser_Collection wakeUpCollection($rows)
 */
class QueueUserTable extends Entity\DataManager
{
	/**
	 * @inheritdoc
	 */
	public static function getTableName()
	{
		return 'b_voximplant_queue_user';
	}

	/**
	 * @inheritdoc
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			'QUEUE_ID' => new Entity\IntegerField('QUEUE_ID'),
			'USER_ID' => new Entity\IntegerField('USER_ID'),
			'STATUS' => new Entity\StringField('STATUS', array(
				'size' => 50
			)),
			'LAST_ACTIVITY_DATE' => new Entity\DatetimeField('LAST_ACTIVITY_DATE'),
			'USER' => new Entity\ReferenceField('USER', '\Bitrix\Voximplant\Model\User', array(
				'=this.USER_ID' => 'ref.ID'
			)),
			'QUEUE' => new Entity\ReferenceField('QUEUE', QueueTable::getEntity(), array(
				'=this.QUEUE_ID' => 'ref.ID'
			)),
			'IS_ONLINE_CUSTOM' => new Entity\ExpressionField(
				'IS_ONLINE_CUSTOM', 
				'CASE WHEN %s > '.\CVoxImplantUser::GetLastActivityDateAgo().' THEN \'Y\' ELSE \'N\' END', array('USER.LAST_ACTIVITY_DATE')
			)
		);
	}

	public static function deleteByQueueId($queueId)
	{
		$queueId = (int)$queueId;
		if($queueId <= 0)
			throw new ArgumentException('Queue id should be greater than zero', 'queueId');

		$connection = Application::getConnection();
		$entity = self::getEntity();

		$sql = "DELETE FROM ".$entity->getDBTableName()." WHERE QUEUE_ID = ".$queueId;
		$connection->queryExecute($sql);

		$result = new Entity\DeleteResult();
		return $result;
	}

	public static function deleteByUserId($userId)
	{
		$userId = (int)$userId;
		if($userId <= 0)
			throw new ArgumentException('User id should be greater than zero', 'userId');

		$connection = Application::getConnection();
		$entity = self::getEntity();

		$sql = "DELETE FROM ".$entity->getDBTableName()." WHERE USER_ID = ".$userId;
		$connection->queryExecute($sql);

		$result = new Entity\DeleteResult();
		return $result;
	}
}
<?php
namespace Bitrix\ImOpenLines\Model;

use \Bitrix\Main,
	\Bitrix\Main\Localization\Loc;

use \Bitrix\Main\ORM\Event,
	\Bitrix\Main\ORM\Query\Join,
	\Bitrix\Main\ORM\EventResult,
	\Bitrix\Main\ORM\Fields\StringField,
	\Bitrix\Main\ORM\Fields\IntegerField,
	\Bitrix\Main\ORM\Fields\DatetimeField,
	\Bitrix\Main\ORM\Fields\Relations\Reference;

Loc::loadMessages(__FILE__);

/**
 * Class QueueTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CONFIG_ID int mandatory
 * <li> USER_ID int mandatory
 * <li> LAST_ACTIVITY_DATE datetime optional
 * <li> LAST_ACTIVITY_DATE_EXACT bigint optional
 * </ul>
 *
 * @package Bitrix\Imopenlines
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Queue_Query query()
 * @method static EO_Queue_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Queue_Result getById($id)
 * @method static EO_Queue_Result getList(array $parameters = array())
 * @method static EO_Queue_Entity getEntity()
 * @method static \Bitrix\ImOpenLines\Model\EO_Queue createObject($setDefaultValues = true)
 * @method static \Bitrix\ImOpenLines\Model\EO_Queue_Collection createCollection()
 * @method static \Bitrix\ImOpenLines\Model\EO_Queue wakeUpObject($row)
 * @method static \Bitrix\ImOpenLines\Model\EO_Queue_Collection wakeUpCollection($rows)
 */

class QueueTable extends Main\ORM\Data\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_imopenlines_queue';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	public static function getMap()
	{
		return [
			new IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('QUEUE_ENTITY_ID_FIELD'),
			]),
			new IntegerField('SORT'),
			new IntegerField('CONFIG_ID', [
				'required' => true,
				'title' => Loc::getMessage('QUEUE_ENTITY_CONFIG_ID_FIELD'),
			]),
			new IntegerField('USER_ID', [
				'required' => true,
				'title' => Loc::getMessage('QUEUE_ENTITY_CONFIG_ID_FIELD'),
			]),
			new IntegerField('DEPARTMENT_ID', [
				'default_value' => 0,
			]),
			new DatetimeField('LAST_ACTIVITY_DATE', [
				'title' => Loc::getMessage('QUEUE_ENTITY_LAST_ACTIVITY_DATE_FIELD'),
				'default_value' => [__CLASS__, 'getCurrentDate'],
			]),
			new IntegerField('LAST_ACTIVITY_DATE_EXACT', [
				'title' => Loc::getMessage('QUEUE_ENTITY_LAST_ACTIVITY_DATE_EXACT_FIELD'),
			]),
			new StringField('USER_NAME', [
				'validation' => [__CLASS__, 'validateString'],
				'title' => Loc::getMessage('QUEUE_ENTITY_USER_NAME_FIELD'),
			]),
			new StringField('USER_WORK_POSITION', [
				'validation' => [__CLASS__, 'validateString'],
				'title' => Loc::getMessage('QUEUE_ENTITY_USER_WORK_POSITION_FIELD'),
			]),
			new StringField('USER_AVATAR', [
				'title' => Loc::getMessage('QUEUE_ENTITY_USER_AVATAR_FIELD'),
			]),
			new IntegerField('USER_AVATAR_ID', [
				'title' => Loc::getMessage('QUEUE_ENTITY_USER_AVATAR_FILE_ID_FIELD'),
				'default_value' => 0,
			]),
			new Reference(
				'USER',
				\Bitrix\Main\UserTable::class,
				Join::on('this.USER_ID', 'ref.ID')
			),
			new Reference(
				'CONFIG',
				ConfigTable::class,
				Join::on('this.CONFIG_ID', 'ref.ID')
			)
		];
	}

	/**
	 * @param Event $event
	 * @return EventResult|void
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function onDelete(Event $event)
	{
		$result = new EventResult;
		$userQueueId = $event->getParameters()['primary']['ID'];

		$userAvatarId = self::getList([
			'select' => ['USER_AVATAR_ID'],
			'filter' => ['=ID' => $userQueueId]
		])->fetch()['USER_AVATAR_ID'];

		if(
			!empty($userAvatarId) &&
			$userAvatarId > 0
		)
		{
			\CFile::Delete($userAvatarId);
		}

		return $result;
	}

	/**
	 * Return current date for LAST_ACTIVITY_DATE field.
	 *
	 * @return Main\Type\DateTime
	 * @throws Main\ObjectException
	 */
	public static function getCurrentDate()
	{
		return new \Bitrix\Main\Type\DateTime();
	}

	/**
	 * @return array
	 * @throws Main\ArgumentTypeException
	 */
	public static function validateString()
	{
		return [
			new Main\Entity\Validator\Length(null, 255),
		];
	}
}
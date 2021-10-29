<?php
namespace Bitrix\ImOpenLines\Model;

use Bitrix\Main\UserTable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\Validator\Length;

use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\EventResult;
use Bitrix\ImOpenLines\Queue\Cache;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\Relations\Reference;

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

class QueueTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_imopenlines_queue';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap(): array
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
				UserTable::class,
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
	 * @return EventResult
	 */
	public static function OnAdd(Event $event): EventResult
	{
		$result = new EventResult;

		$fields = $event->getParameters()['fields'];

		if(!empty($fields['USER_ID']))
		{
			$cache = new Cache();
			$cache->setUserId($fields['USER_ID']);

			if(!empty($fields['CONFIG_ID']))
			{
				$cache->setLineId($fields['CONFIG_ID']);
			}

			$cache->delete();
		}

		return $result;
	}

	/**
	 * @param Event $event
	 * @return EventResult
	 */
	public static function OnUpdate(Event $event): EventResult
	{
		$result = new EventResult;
		$userQueueId = $event->getParameters()['primary']['ID'];
		$fields = $event->getParameters()['fields'];

		if(
			isset($fields['CONFIG_ID'])
			|| isset($fields['USER_ID'])
			|| isset($fields['DEPARTMENT_ID'])
			|| isset($fields['USER_NAME'])
			|| isset($fields['USER_WORK_POSITION'])
			|| isset($fields['USER_AVATAR'])
			|| isset($fields['USER_AVATAR_ID'])
		)
		{
			$data = self::getList([
				'select' => [
					'CONFIG_ID',
					'USER_ID'
				],
				'filter' => [
					'=ID' => $userQueueId
				]
			])->fetch();

			if(!empty($data['USER_ID']))
			{
				$cache = new Cache();
				$cache->setUserId($data['USER_ID']);

				if(!empty($data['CONFIG_ID']))
				{
					$cache->setLineId($data['CONFIG_ID']);
				}

				$cache->delete();
			}
		}

		return $result;
	}

	/**
	 * @param Event $event
	 * @return EventResult
	 */
	public static function onDelete(Event $event): EventResult
	{
		$result = new EventResult;
		$userQueueId = $event->getParameters()['primary']['ID'];

		$data = self::getList([
			'select' => [
				'CONFIG_ID',
				'USER_ID',
				'USER_AVATAR_ID'
			],
			'filter' => [
				'=ID' => $userQueueId
			]
		])->fetch();

		if(
			!empty($data['USER_AVATAR_ID'])
			&& $data['USER_AVATAR_ID'] > 0
		)
		{
			\CFile::Delete($data['USER_AVATAR_ID']);
		}

		if(!empty($data['USER_ID']))
		{
			$cache = new Cache();
			$cache->setUserId($data['USER_ID']);

			if(!empty($data['CONFIG_ID']))
			{
				$cache->setLineId($data['CONFIG_ID']);
			}

			$cache->delete();
		}

		return $result;
	}

	/**
	 * Return current date for LAST_ACTIVITY_DATE field.
	 *
	 * @return DateTime
	 */
	public static function getCurrentDate(): DateTime
	{
		return new DateTime();
	}

	/**
	 * @return Length[]
	 */
	public static function validateString(): array
	{
		return [
			new Length(null, 255),
		];
	}
}
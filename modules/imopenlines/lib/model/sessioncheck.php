<?php
namespace Bitrix\ImOpenLines\Model;

use \Bitrix\Main,
	\Bitrix\Main\ORM\Event,
	\Bitrix\Main\ORM\Query\Join,
	\Bitrix\Main\ORM\EventResult,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\Main\ORM\Data\DataManager,
	\Bitrix\Main\ORM\Fields\EnumField,
	\Bitrix\Main\ORM\Fields\BooleanField,
	\Bitrix\Main\ORM\Fields\IntegerField,
	\Bitrix\Main\ORM\Fields\DatetimeField,
	\Bitrix\Main\ORM\Fields\Relations\Reference;

use \Bitrix\ImOpenLines\Session;

Loc::loadMessages(__FILE__);

/**
 * Class SessionCheckTable
 *
 * Fields:
 * <ul>
 * <li> SESSION_ID int mandatory
 * <li> DATE_CLOSE datetime optional
 * <li> DATE_QUEUE datetime optional
 * <li> DATE_MAIL datetime optional
 * <li> DATE_NO_ANSWER datetime optional
 * </ul>
 *
 * @package Bitrix\Imopenlines
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SessionCheck_Query query()
 * @method static EO_SessionCheck_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_SessionCheck_Result getById($id)
 * @method static EO_SessionCheck_Result getList(array $parameters = array())
 * @method static EO_SessionCheck_Entity getEntity()
 * @method static \Bitrix\ImOpenLines\Model\EO_SessionCheck createObject($setDefaultValues = true)
 * @method static \Bitrix\ImOpenLines\Model\EO_SessionCheck_Collection createCollection()
 * @method static \Bitrix\ImOpenLines\Model\EO_SessionCheck wakeUpObject($row)
 * @method static \Bitrix\ImOpenLines\Model\EO_SessionCheck_Collection wakeUpCollection($rows)
 */

class SessionCheckTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_imopenlines_session_check';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 * @throws Main\SystemException
	 */
	public static function getMap()
	{
		return array(
			new IntegerField('SESSION_ID', [
				'primary' => true,
				'title' => Loc::getMessage('SESSION_CHECK_ENTITY_SESSION_ID_FIELD'),
			]),
			new DatetimeField('DATE_CLOSE', [
				'title' => Loc::getMessage('SESSION_CHECK_ENTITY_DATE_CLOSE_FIELD')
			]),
			new DatetimeField('DATE_QUEUE', [
				'title' => Loc::getMessage('SESSION_CHECK_ENTITY_DATE_QUEUE_FIELD')
			]),
			new DatetimeField('DATE_MAIL', [
				'title' => Loc::getMessage('SESSION_CHECK_ENTITY_DATE_MAIL_FIELD')
			]),
			new DatetimeField('DATE_NO_ANSWER', [
				'title' => Loc::getMessage('SESSION_CHECK_ENTITY_DATE_NO_ANSWER_FIELD')
			]),
			//if you add a new return reason, you need to add a list of possible values here: \Bitrix\ImOpenLines\Queue
			new EnumField('REASON_RETURN', [
				'values' => [
					'VACATION',
					'NONWORKING',
					'REMOVING',
					'DISMISSAL',
					'NOT_AVAILABLE',
					'OFFLINE',
					'DEFAULT'
				],
				'default_value' => 'DEFAULT',
			]),
			new BooleanField('UNDISTRIBUTED', [
				'values' => ['N', 'Y'],
				'default_value' => 'N',
			]),
			new Reference(
				'SESSION',
				SessionTable::class,
				Join::on('this.SESSION_ID', 'ref.ID')
			)
		);
	}

	/**
	 * @param Event $event
	 * @return EventResult|void
	 */
	public static function OnAfterAdd(Event $event)
	{
		$result = new EventResult;
		Session::deleteQueueFlagCache();

		return $result;
	}

	/**
	 * @param Event $event
	 * @return EventResult|void
	 */
	public static function onBeforeUpdate(Event $event)
	{
		$result = new EventResult;

		$data = $event->getParameter('fields');

		if (array_key_exists('DATE_QUEUE', $data) && empty($data['DATE_QUEUE']) && !array_key_exists('UNDISTRIBUTED', $data))
		{
			$data['UNDISTRIBUTED'] = 'N';

			$result->modifyFields($data);
		}

		return $result;
	}

	/**
	 * @param Event $event
	 * @return EventResult|void
	 */
	public static function OnAfterUpdate(Event $event)
	{
		$result = new EventResult;
		$data = $event->getParameter('fields');

		if (isset($data['DATE_QUEUE']) && !empty($data['DATE_QUEUE']))
		{
			Session::deleteQueueFlagCache(Session::CACHE_QUEUE);
		}

		if (isset($data['DATE_CLOSE']) && !empty($data['DATE_CLOSE']))
		{
			Session::deleteQueueFlagCache(Session::CACHE_CLOSE);
		}

		if (isset($data['DATE_MAIL']) && !empty($data['DATE_MAIL']))
		{
			Session::deleteQueueFlagCache(Session::CACHE_MAIL);
		}

		if (isset($data['DATE_NO_ANSWER']) && !empty($data['DATE_NO_ANSWER']))
		{
			Session::deleteQueueFlagCache(Session::CACHE_NO_ANSWER);
		}

		return $result;
	}
}
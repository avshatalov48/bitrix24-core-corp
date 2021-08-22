<?php
namespace Bitrix\ImOpenLines\Model;

use \Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Entity\DataManager,
	\Bitrix\Main\Type\DateTime;

use \Bitrix\Main\Entity\IntegerField,
	\Bitrix\Main\Entity\DatetimeField,
	\Bitrix\Main\Entity\BooleanField,
	\Bitrix\Main\Entity\StringField,
	\Bitrix\Main\ORM\Query\Join,
	\Bitrix\Main\ORM\Fields\Relations\Reference;

Loc::loadMessages(__FILE__);

/**
 * Class SessionKpiMessagesTable
 * @package Bitrix\ImOpenLines\Model
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SessionKpiMessages_Query query()
 * @method static EO_SessionKpiMessages_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_SessionKpiMessages_Result getById($id)
 * @method static EO_SessionKpiMessages_Result getList(array $parameters = array())
 * @method static EO_SessionKpiMessages_Entity getEntity()
 * @method static \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages createObject($setDefaultValues = true)
 * @method static \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages_Collection createCollection()
 * @method static \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages wakeUpObject($row)
 * @method static \Bitrix\ImOpenLines\Model\EO_SessionKpiMessages_Collection wakeUpCollection($rows)
 */
class SessionKpiMessagesTable extends DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_imopenlines_session_kpi_messages';
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getMap()
	{
		return array(
			new IntegerField( 'ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			new IntegerField('SESSION_ID', array(
				'required' => true
			)),
			new IntegerField('MESSAGE_ID', array(
				'required' => true
			)),
			new BooleanField('IS_FIRST_MESSAGE', array(
				'values' => array('N', 'Y'),
				'default_value' => 'Y'
			)),
			new DatetimeField('TIME_RECEIVED', array(
				'required' => true,
				'default_value' => new DateTime
			)),
			new DatetimeField('TIME_ANSWER', array()),
			new DatetimeField('TIME_EXPIRED', array()),
			new DatetimeField('TIME_STOP', array()),
			new StringField('TIME_STOP_HISTORY', array(
				'serialized' => true
			)),
			new BooleanField('IS_SENT_EXPIRED_NOTIFICATION', array(
				'values' => array('N', 'Y'),
				'default_value' => 'N'
			)),
			new Reference(
				'SESSION',
				SessionTable::class,
				Join::on('this.SESSION_ID', 'ref.ID')
			),
		);
	}
}
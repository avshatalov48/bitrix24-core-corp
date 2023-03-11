<?php
namespace Bitrix\ImOpenLines\Model;

use Bitrix\Main;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\Validator\Length;

use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\Relations\Reference;

use Bitrix\ImOpenLines\Config;
use Bitrix\ImOpenLines\Model\ConfigStatisticTable;

Loc::loadMessages(__FILE__);

/**
 * Class ConfigTable
 *
 * @package Bitrix\Imopenlines
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Config_Query query()
 * @method static EO_Config_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Config_Result getById($id)
 * @method static EO_Config_Result getList(array $parameters = array())
 * @method static EO_Config_Entity getEntity()
 * @method static \Bitrix\ImOpenLines\Model\EO_Config createObject($setDefaultValues = true)
 * @method static \Bitrix\ImOpenLines\Model\EO_Config_Collection createCollection()
 * @method static \Bitrix\ImOpenLines\Model\EO_Config wakeUpObject($row)
 * @method static \Bitrix\ImOpenLines\Model\EO_Config_Collection wakeUpCollection($rows)
 */

class ConfigTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_imopenlines_config';
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
				'title' => Loc::getMessage('CONFIG_ENTITY_ID_FIELD'),
			]),
			new BooleanField('ACTIVE', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('CONFIG_ENTITY_ACTIVE_FIELD'),
				'default_value' => 'Y',
			]),
			new StringField('LINE_NAME', [
				'validation' => [__CLASS__, 'validateText255'],
				'title' => Loc::getMessage('CONFIG_ENTITY_LINE_NAME_FIELD'),
			]),
			new BooleanField('CRM', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('CONFIG_ENTITY_CRM_FIELD'),
				'default_value' => 'Y',
			]),
			new StringField('CRM_CREATE', [
				'validation' => [__CLASS__, 'validateText50'],
				'title' => Loc::getMessage('CONFIG_ENTITY_CRM_CREATE_FIELD'),
				'default_value' => 'lead',
			]),
			new StringField('CRM_CREATE_SECOND', [
				'validation' => [__CLASS__, 'validateText50'],
				'title' => Loc::getMessage('CONFIG_ENTITY_CRM_CREATE_SECOND_FIELD'),
				'default_value' => '',
			]),
			new StringField('CRM_CREATE_THIRD', [
				'validation' => [__CLASS__, 'validateText50'],
				'title' => Loc::getMessage('CONFIG_ENTITY_CRM_CREATE_THIRD_FIELD'),
				'default_value' => '',
			]),
			new BooleanField('CRM_FORWARD', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('CONFIG_ENTITY_CRM_FORWARD_FIELD'),
				'default_value' => 'Y',
			]),
			new BooleanField('CRM_CHAT_TRACKER', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('CONFIG_ENTITY_CRM_CHAT_TRACKER_FIELD'),
				'default_value' => 'Y',
			]),
			new BooleanField('CRM_TRANSFER_CHANGE', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('CONFIG_ENTITY_CRM_TRANSFER_CHANGE_FIELD'),
				'default_value' => 'Y',
			]),
			new StringField('CRM_SOURCE', [
				'validation' => [__CLASS__, 'validateText50'],
				'title' => Loc::getMessage('CONFIG_ENTITY_CRM_SOURCE_FIELD'),
				'default_value' => 'create',
			]),
			new IntegerField('QUEUE_TIME', [
				'title' => Loc::getMessage('CONFIG_ENTITY_QUEUE_TIME_FIELD_NEW'),
				'default_value' => '60',
			]),
			new IntegerField('NO_ANSWER_TIME', [
				'title' => Loc::getMessage('CONFIG_ENTITY_NO_ANSWER_TIME_FIELD'),
				'default_value' => '60',
			]),
			new StringField('QUEUE_TYPE', [
				'validation' => [__CLASS__, 'validateText50'],
				'title' => Loc::getMessage('CONFIG_ENTITY_QUEUE_TYPE_FIELD'),
				'default_value' => 'all',
			]),
			new BooleanField('CHECK_AVAILABLE', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('CONFIG_ENTITY_CHECK_AVAILABLE_FIELD'),
				'default_value' => 'N',
			]),
			new BooleanField('WATCH_TYPING', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('CONFIG_ENTITY_WATCH_TYPING_FIELD'),
				'default_value' => 'N',
			]),
			new BooleanField('WELCOME_BOT_ENABLE', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('CONFIG_ENTITY_WELCOME_BOT_ENABLE_FIELD'),
				'default_value' => 'N',
			]),
			new BooleanField('WELCOME_MESSAGE', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('CONFIG_ENTITY_WELCOME_MESSAGE_FIELD'),
				'default_value' => 'Y',
			]),
			new TextField('WELCOME_MESSAGE_TEXT', [
				'title' => Loc::getMessage('CONFIG_ENTITY_WELCOME_MESSAGE_TEXT_FIELD_NEW')
			]),
			new BooleanField('VOTE_MESSAGE', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('CONFIG_ENTITY_VOTE_MESSAGE_FIELD'),
				'default_value' => 'Y',
			]),
			new IntegerField('VOTE_TIME_LIMIT', [
				'default_value' => '0',
			]),
			new BooleanField('VOTE_BEFORE_FINISH', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('CONFIG_ENTITY_VOTE_BEFORE_FINISH_FIELD'),
				'default_value' => 'Y',
			]),
			new BooleanField('VOTE_CLOSING_DELAY', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('CONFIG_ENTITY_VOTE_CLOSING_DELAY_FIELD_NEW'),
				'default_value' => 'N',
			]),
			new TextField('VOTE_MESSAGE_1_TEXT', [
				'title' => Loc::getMessage('CONFIG_ENTITY_VOTE_MESSAGE_1_TEXT_FIELD')
			]),
			new TextField('VOTE_MESSAGE_1_LIKE', [
				'title' => Loc::getMessage('CONFIG_ENTITY_VOTE_MESSAGE_1_LIKE_FIELD')
			]),
			new TextField('VOTE_MESSAGE_1_DISLIKE', [
				'title' => Loc::getMessage('CONFIG_ENTITY_VOTE_MESSAGE_1_DISLIKE_FIELD')
			]),
			new TextField('VOTE_MESSAGE_2_TEXT', [
				'title' => Loc::getMessage('CONFIG_ENTITY_VOTE_MESSAGE_2_TEXT_FIELD')
			]),
			new TextField('VOTE_MESSAGE_2_LIKE', [
				'title' => Loc::getMessage('CONFIG_ENTITY_VOTE_MESSAGE_2_LIKE_FIELD')
			]),
			new TextField('VOTE_MESSAGE_2_DISLIKE', [
				'title' => Loc::getMessage('CONFIG_ENTITY_VOTE_MESSAGE_2_DISLIKE_FIELD')
			]),
			new BooleanField('AGREEMENT_MESSAGE', [
				'values' => ['N', 'Y'],
				'default_value' => 'N',
			]),
			new IntegerField('AGREEMENT_ID', [
				'default_value' => '0',
			]),
			new BooleanField('CATEGORY_ENABLE', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('CONFIG_ENTITY_CATEGORY_ENABLE_FIELD'),
				'default_value' => 'N',
			]),
			new IntegerField('CATEGORY_ID', [
				'title' => Loc::getMessage('CONFIG_ENTITY_CATEGORY_ID_FIELD'),
				'default_value' => '0',
			]),
			new StringField('WELCOME_BOT_JOIN', [
				'validation' => [__CLASS__, 'validateText50'],
				'title' => Loc::getMessage('CONFIG_ENTITY_WELCOME_BOT_JOIN_FIELD'),
				'default_value' => 'first',
			]),
			new IntegerField('WELCOME_BOT_ID', [
				'title' => Loc::getMessage('CONFIG_ENTITY_BOT_ID_FIELD'),
				'default_value' => '0',
			]),
			new IntegerField('WELCOME_BOT_TIME', [
				'title' => Loc::getMessage('CONFIG_ENTITY_BOT_TIME_FIELD'),
				'default_value' => '600',
			]),
			new StringField('WELCOME_BOT_LEFT', [
				'validation' => [__CLASS__, 'validateText50'],
				'title' => Loc::getMessage('CONFIG_ENTITY_WELCOME_BOT_LEFT_FIELD'),
				'default_value' => 'queue',
			]),
			new StringField('NO_ANSWER_RULE', [
				'validation' => [__CLASS__, 'validateText50'],
				'title' => Loc::getMessage('CONFIG_ENTITY_NO_ANSWER_RULE_FIELD'),
				'default_value' => Config::RULE_NONE,
				'fetch_data_modification' => function ()
				{
					return [
						function ($value)
						{
							if(Config::RULE_FORM == $value || Config::RULE_QUEUE == $value)
							{
								$value = Config::RULE_NONE;
							}

							return $value;
						}
					];
				}
			]),
			new IntegerField('NO_ANSWER_FORM_ID', [
				'title' => Loc::getMessage('CONFIG_ENTITY_NO_ANSWER_FORM_ID_FIELD'),
				'default_value' => '0',
			]),
			new IntegerField('NO_ANSWER_BOT_ID', [
				'title' => Loc::getMessage('CONFIG_ENTITY_NO_ANSWER_BOT_ID_FIELD'),
				'default_value' => '0',
			]),
			new TextField('NO_ANSWER_TEXT', [
				'title' => Loc::getMessage('CONFIG_ENTITY_NO_ANSWER_TEXT_FIELD')
			]),
			new BooleanField('WORKTIME_ENABLE', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('CONFIG_ENTITY_WORKTIME_ENABLE_FIELD'),
				'default_value' => 'N',
			]),
			new StringField('WORKTIME_FROM', [
				'validation' => [__CLASS__, 'validateText5'],
				'title' => Loc::getMessage('CONFIG_ENTITY_WORKTIME_FROM_FIELD'),
				'default_value' => '9',
			]),
			new StringField('WORKTIME_TO', [
				'validation' => [__CLASS__, 'validateText5'],
				'title' => Loc::getMessage('CONFIG_ENTITY_WORKTIME_TO_FIELD'),
				'default_value' => '18.30',
			]),
			new StringField('WORKTIME_TIMEZONE', [
				'validation' => [__CLASS__, 'validateText50'],
				'title' => Loc::getMessage('CONFIG_ENTITY_WORKTIME_TIMEZONE_FIELD'),
			]),
			new StringField('WORKTIME_HOLIDAYS', [
				'validation' => [__CLASS__, 'validateText2000'],
				'title' => Loc::getMessage('CONFIG_ENTITY_WORKTIME_HOLIDAYS_FIELD'),
			]),
			new StringField('WORKTIME_DAYOFF', [
				'validation' => [__CLASS__, 'validateText20'],
				'title' => Loc::getMessage('CONFIG_ENTITY_WORKTIME_DAYOFF_FIELD'),
			]),
			new StringField('WORKTIME_DAYOFF_RULE', [
				'validation' => [__CLASS__, 'validateText50'],
				'title' => Loc::getMessage('CONFIG_ENTITY_WORKTIME_DAYOFF_RULE_FIELD'),
				'default_value' => 'form',
			]),
			new IntegerField('WORKTIME_DAYOFF_FORM_ID', [
				'title' => Loc::getMessage('CONFIG_ENTITY_WORKTIME_DAYOFF_FORM_ID_FIELD'),
				'default_value' => '0',
			]),
			new IntegerField('WORKTIME_DAYOFF_BOT_ID', [
				'title' => Loc::getMessage('CONFIG_ENTITY_WORKTIME_DAYOFF_BOT_ID_FIELD'),
				'default_value' => '0',
			]),
			new TextField('WORKTIME_DAYOFF_TEXT', [
				'title' => Loc::getMessage('CONFIG_ENTITY_WORKTIME_DAYOFF_TEXT_FIELD')
			]),
			new StringField('CLOSE_RULE', [
				'validation' => [__CLASS__, 'validateText50'],
				'title' => Loc::getMessage('CONFIG_ENTITY_CLOSE_RULE_FIELD'),
				'default_value' => 'text',
			]),
			new IntegerField('CLOSE_FORM_ID', [
				'title' => Loc::getMessage('CONFIG_ENTITY_CLOSE_FORM_ID_FIELD'),
				'default_value' => '0',
			]),
			new IntegerField('CLOSE_BOT_ID', [
				'title' => Loc::getMessage('CONFIG_ENTITY_CLOSE_BOT_ID_FIELD'),
				'default_value' => '0',
			]),
			new TextField('CLOSE_TEXT', [
				'title' => Loc::getMessage('CONFIG_ENTITY_CLOSE_TEXT_FIELD')
			]),
			new IntegerField('FULL_CLOSE_TIME', [
				'title' => Loc::getMessage('CONFIG_ENTITY_FULL_CLOSE_TIME_FIELD'),
				'default_value' => '10',
			]),
			new StringField('AUTO_CLOSE_RULE', [
				'validation' => [__CLASS__, 'validateText50'],
				'title' => Loc::getMessage('CONFIG_ENTITY_AUTO_CLOSE_RULE_FIELD'),
				'default_value' => 'none',
			]),
			new IntegerField('AUTO_CLOSE_FORM_ID', [
				'title' => Loc::getMessage('CONFIG_ENTITY_AUTO_CLOSE_FORM_ID_FIELD'),
				'default_value' => '0',
			]),
			new IntegerField('AUTO_CLOSE_BOT_ID', [
				'title' => Loc::getMessage('CONFIG_ENTITY_AUTO_CLOSE_BOT_ID_FIELD'),
				'default_value' => '0',
			]),
			new IntegerField('AUTO_CLOSE_TIME', [
				'title' => Loc::getMessage('CONFIG_ENTITY_AUTO_CLOSE_TIME_FIELD_NEW'),
				'default_value' => '14400',
			]),
			new TextField('AUTO_CLOSE_TEXT', [
				'title' => Loc::getMessage('CONFIG_ENTITY_AUTO_CLOSE_TEXT_FIELD_NEW')
			]),
			new IntegerField('AUTO_EXPIRE_TIME', [
				'title' => Loc::getMessage('CONFIG_ENTITY_AUTO_EXPIRE_TIME_FIELD'),
				'default_value' => '86400',
			]),
			new DatetimeField('DATE_CREATE', [
				'title' => Loc::getMessage('CONFIG_ENTITY_DATE_CREATE_FIELD'),
				'default_value' => [__CLASS__, 'getCurrentDate'],
			]),
			new DatetimeField('DATE_MODIFY', [
				'title' => Loc::getMessage('CONFIG_ENTITY_DATE_MODIFY_FIELD'),
				'default_value' => [__CLASS__, 'getCurrentDate'],
			]),
			new IntegerField('MODIFY_USER_ID', [
				'title' => Loc::getMessage('CONFIG_ENTITY_MODIFY_USER_ID_FIELD'),
				'required' => true,
			]),
			new BooleanField('TEMPORARY', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('CONFIG_ENTITY_TEMPORARY_FIELD'),
				'default_value' => 'Y',
			]),
			new StringField('XML_ID', [
				'validation' => [__CLASS__, 'validateText255'],
			]),
			new StringField('LANGUAGE_ID', [
				'validation' => [__CLASS__, 'validateText2'],
			]),
			new Reference(
				'STATISTIC',
				ConfigStatisticTable::class,
				Join::on('this.ID', 'ref.CONFIG_ID')
			),
			new IntegerField('QUICK_ANSWERS_IBLOCK_ID'),
			new IntegerField('SESSION_PRIORITY', [
				'default_value' => '0',
			]),
			new StringField('TYPE_MAX_CHAT', [
				'validation' => [__CLASS__, 'validateText50'],
				'default_value' => 'ANSWERED_NEW',
			]),
			new IntegerField('MAX_CHAT', [
				'default_value' => '0',
			]),
			new StringField('OPERATOR_DATA', [
				'validation' => [__CLASS__, 'validateText50'],
				'title' => Loc::getMessage('CONFIG_ENTITY_OPERATOR_DATA_FIELD'),
				'default_value' => 'profile',
			]),
			new StringField('DEFAULT_OPERATOR_DATA', [
				'title' => Loc::getMessage('CONFIG_ENTITY_DEFAULT_OPERATOR_DATA_FIELD'),
				'serialized' => true
			]),
			new IntegerField('KPI_FIRST_ANSWER_TIME', [
				'title' => Loc::getMessage('CONFIG_ENTITY_KPI_FIRST_ANSWER_TIME'),
				'default_value' => '0',
			]),
			new BooleanField('KPI_FIRST_ANSWER_ALERT', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('CONFIG_ENTITY_KPI_ANSWER_ALERT'),
				'default_value' => 'N',
			]),
			new StringField('KPI_FIRST_ANSWER_LIST', [
				'title' => Loc::getMessage('CONFIG_ENTITY_KPI_ANSWER_LIST'),
				'serialized' => true
			]),
			new StringField('KPI_FIRST_ANSWER_TEXT', [
				'title' => Loc::getMessage('CONFIG_ENTITY_KPI_ANSWER_TEXT')
			]),
			new IntegerField('KPI_FURTHER_ANSWER_TIME', [
				'title' => Loc::getMessage('CONFIG_ENTITY_KPI_FURTHER_ANSWER_TIME'),
				'default_value' => '0',
			]),
			new BooleanField('KPI_FURTHER_ANSWER_ALERT', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('CONFIG_ENTITY_KPI_ANSWER_ALERT'),
				'default_value' => 'N',
			]),
			new StringField('KPI_FURTHER_ANSWER_LIST', [
				'title' => Loc::getMessage('CONFIG_ENTITY_KPI_ANSWER_LIST'),
				'serialized' => true
			]),
			new StringField('KPI_FURTHER_ANSWER_TEXT', [
				'title' => Loc::getMessage('CONFIG_ENTITY_KPI_ANSWER_TEXT')
			]),
			new BooleanField('KPI_CHECK_OPERATOR_ACTIVITY', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('CONFIG_ENTITY_KPI_CHECK_OPERATOR_ACTIVITY'),
				'default_value' => 'N',
			]),
			new BooleanField('SEND_NOTIFICATION_EMPTY_QUEUE', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('CONFIG_ENTITY_SEND_NOTIFICATION_EMPTY_QUEUE'),
				'default_value' => 'N',
			]),
			new BooleanField('USE_WELCOME_FORM', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('CONFIG_ENTITY_USE_WELCOME_FORM'),
				'default_value' => 'Y',
			]),
			new IntegerField('WELCOME_FORM_ID', [
				'title' => Loc::getMessage('CONFIG_ENTITY_WELCOME_FORM_ID')
			]),
			new BooleanField('WELCOME_FORM_DELAY', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('CONFIG_ENTITY_WELCOME_FORM_DELAY'),
				'default_value' => 'Y',
			]),
			new BooleanField('SEND_WELCOME_EACH_SESSION', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('CONFIG_ENTITY_SEND_WELCOME_EACH_SESSION'),
				'default_value' => 'N',
			]),
			new BooleanField('CONFIRM_CLOSE', [
				'values' => ['N', 'Y'],
				'default_value' => 'N',
			]),
			new BooleanField('IGNORE_WELCOME_FORM_RESPONSIBLE', [
				'values' => ['N', 'Y'],
				'default_value' => 'N',
			]),
		];
	}

	/**
	 * @param Event $event
	 * @return EventResult|void
	 */
	public static function onDelete(Event $event)
	{
		$result = new EventResult;
		$configId = $event->getParameters()['primary']['ID'];

		$defaultOperatorData = self::getList([
			'select' => ['DEFAULT_OPERATOR_DATA'],
			'filter' => ['=ID' => $configId]
		])->fetch()['DEFAULT_OPERATOR_DATA'];

		if(
			!empty($defaultOperatorData['AVATAR_ID']) &&
			$defaultOperatorData['AVATAR_ID'] > 0
		)
		{
			\CFile::Delete($defaultOperatorData['AVATAR_ID']);
		}

		return $result;
	}

	/**
	 * Returns validators for a text field of up to 2000 characters.
	 *
	 * @return array
	 */
	public static function validateText2000(): array
	{
		return [
			new Length(null, 2000),
		];
	}
	/**
	 * Returns validators for a text field of up to 255 characters.
	 *
	 * @return array
	 */
	public static function validateText255(): array
	{
		return [
			new Length(null, 255),
		];
	}
	/**
	 * Returns validators for a text field of up to 50 characters.
	 *
	 * @return array
	 */
	public static function validateText50(): array
	{
		return [
			new Length(null, 50),
		];
	}
	/**
	 * Returns validators for a text field of up to 20 characters.
	 *
	 * @return array
	 */
	public static function validateText20(): array
	{
		return [
			new Length(null, 20),
		];
	}
	/**
	 * Returns validators for a text field of up to 5 characters.
	 *
	 * @return array
	 */
	public static function validateText5(): array
	{
		return [
			new Length(null, 5),
		];
	}
	/**
	 * Returns validators for a text field of up to 2 characters.
	 *
	 * @return array
	 */
	public static function validateText2(): array
	{
		return [
			new Length(null, 2),
		];
	}

	/**
	 * Return current date for DATE_CREATE field.
	 *
	 * @return DateTime
	 */
	public static function getCurrentDate(): DateTime
	{
		return new DateTime();
	}
}
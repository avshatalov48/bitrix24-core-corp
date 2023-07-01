<?php
namespace Bitrix\ImOpenLines\Model;

use Bitrix\Main,
	Bitrix\Main\Loader,
	Bitrix\Main\UserTable,
	Bitrix\Main\ORM\Event,
	Bitrix\Main\ORM\Fields\Field,
	Bitrix\Main\ORM\Fields\Validators\Validator,
	Bitrix\Main\ORM\Fields\Validators\LengthValidator,
	Bitrix\Main\Type\DateTime,
	Bitrix\Main\ORM\Query\Join,
	Bitrix\Main\ORM\EventResult,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Search\MapBuilder,
	Bitrix\Main\ORM\Fields\TextField,
	Bitrix\Main\ORM\Data\DataManager,
	Bitrix\Main\ORM\Fields\StringField,
	Bitrix\Main\ORM\Fields\IntegerField,
	Bitrix\Main\ORM\Fields\BooleanField,
	Bitrix\Main\ORM\Fields\DatetimeField,
	Bitrix\Main\ORM\Fields\UserTypeField,
	Bitrix\Main\ORM\Fields\ExpressionField,
	Bitrix\Main\ORM\Fields\Relations\Reference;

use Bitrix\ImOpenLines\Crm,
	Bitrix\ImOpenLines\Integrations\Report\Statistics;

use Bitrix\Im,
	Bitrix\Im\Model\ChatTable,
	Bitrix\Im\Model\MessageTable;

Loc::loadMessages(__FILE__);

/**
 * Class SessionTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> MODE string(255)  default 'input'
 * <li> SOURCE string(255) optional
 * <li> STATUS int optional
 * <li> CONFIG_ID int optional
 * <li> USER_ID int mandatory
 * <li> OPERATOR_ID int mandatory
 * <li> USER_CODE string(255) optional
 * <li> CHAT_ID int mandatory
 * <li> MESSAGE_COUNT int optional
 * <li> START_ID int mandatory
 * <li> END_ID int mandatory
 * <li> CRM bool optional default 'N'
 * <li> CRM_CREATE bool optional default 'N'
 * <li> CRM_ACTIVITY_ID int optional
 * <li> DATE_CREATE datetime optional
 * <li> DATE_MODIFY datetime optional
 * <li> WAIT_ANSWER bool optional default 'Y'
 * <li> WAIT_ACTION bool optional default 'N'
 * <li> VOTE_ACTION bool optional default 'N'
 * <li> CLOSED bool optional default 'N'
 * <li> PAUSE bool optional default 'N'
 * <li> WORKTIME bool optional default 'Y'
 * <li> QUEUE_HISTORY string optional
 * <li> VOTE int optional
 * <li> VOTE_HEAD int optional
 * <li> COMMENT_HEAD text optional
 * </ul>
 *
 * @package Bitrix\Imopenlines
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Session_Query query()
 * @method static EO_Session_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Session_Result getById($id)
 * @method static EO_Session_Entity getEntity()
 * @method static \Bitrix\ImOpenLines\Model\EO_Session createObject($setDefaultValues = true)
 * @method static \Bitrix\ImOpenLines\Model\EO_Session_Collection createCollection()
 * @method static \Bitrix\ImOpenLines\Model\EO_Session wakeUpObject($row)
 * @method static \Bitrix\ImOpenLines\Model\EO_Session_Collection wakeUpCollection($rows)
 */
class SessionTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_imopenlines_session';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return Field[]
	 */
	public static function getMap()
	{
		$result = [
			new IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('SESSION_ENTITY_ID_FIELD'),
			]),
			new StringField('MODE', [
				'validation' => [__CLASS__, 'validateMode'],
				'title' => Loc::getMessage('SESSION_ENTITY_MODE_FIELD'),
				'default_value' => 'input',
			]),
			new StringField('SOURCE', [
				'validation' => [__CLASS__, 'validateSource'],
				'title' => Loc::getMessage('SESSION_ENTITY_SOURCE_FIELD'),
			]),
			new IntegerField('STATUS', [
				'default_value' => '0',
			]),
			new IntegerField('CONFIG_ID', [
				'title' => Loc::getMessage('SESSION_ENTITY_CONFIG_ID_FIELD'),
				'default_value' => '0',
			]),
			new IntegerField('USER_ID', [
				'required' => true,
				'title' => Loc::getMessage('SESSION_ENTITY_USER_ID_FIELD'),
				'default_value' => '0',
			]),
			new Reference(
				'USER',
				UserTable::class,
				Join::on('this.USER_ID', 'ref.ID')
			),
			new IntegerField('OPERATOR_ID', [
				'required' => true,
				'title' => Loc::getMessage('SESSION_ENTITY_OPERATOR_ID_FIELD'),
				'default_value' => '0',
			]),
			new BooleanField('OPERATOR_FROM_CRM', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('SESSION_ENTITY_OPERATOR_FROM_CRM'),
				'default_value' => 'N',
			]),
			new Reference(
				'OPERATOR',
				UserTable::class,
				Join::on('this.OPERATOR_ID', 'ref.ID')
			),
			new StringField('USER_CODE', [
				'validation' => [__CLASS__, 'validateUserCode'],
				'title' => Loc::getMessage('SESSION_ENTITY_USER_CODE_FIELD'),
			]),
			new IntegerField('CHAT_ID', [
				'required' => true,
				'title' => Loc::getMessage('SESSION_ENTITY_CHAT_ID_FIELD'),
				'default_value' => '0',
			]),
			new IntegerField('MESSAGE_COUNT', [
				'title' => Loc::getMessage('SESSION_ENTITY_MESSAGE_FIELD_NEW_NEW'),
				'default_value' => '0',
			]),
			new IntegerField('LIKE_COUNT', [
				'title' => Loc::getMessage('SESSION_ENTITY_LIKE_COUNT_FIELD'),
				'default_value' => '0',
			]),
			new IntegerField('START_ID', [
				'required' => true,
				'title' => Loc::getMessage('SESSION_ENTITY_START_ID_FIELD'),
				'default_value' => '0',
			]),
			new IntegerField('END_ID', [
				'required' => true,
				'title' => Loc::getMessage('SESSION_ENTITY_END_ID_FIELD'),
				'default_value' => '0',
			]),
			new IntegerField('LAST_SEND_MAIL_ID', [
				'required' => true,
				'default_value' => '0',
			]),
			new BooleanField('CRM', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('SESSION_ENTITY_CRM_FIELD'),
				'default_value' => 'N',
			]),
			new BooleanField('CRM_CREATE', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('SESSION_ENTITY_CRM_CREATE_FIELD'),
				'default_value' => 'N',
			]),
			new BooleanField('CRM_CREATE_LEAD', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('SESSION_ENTITY_CRM_CREATE_LEAD'),
				'default_value' => 'N',
			]),
			new BooleanField('CRM_CREATE_COMPANY', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('SESSION_ENTITY_CRM_CREATE_COMPANY'),
				'default_value' => 'N',
			]),
			new BooleanField('CRM_CREATE_CONTACT', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('SESSION_ENTITY_CRM_CREATE_CONTACT'),
				'default_value' => 'N',
			]),
			new BooleanField('CRM_CREATE_DEAL', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('SESSION_ENTITY_CRM_CREATE_DEAL'),
				'default_value' => 'N',
			]),
			new IntegerField('CRM_ACTIVITY_ID', [
				'title' => Loc::getMessage('SESSION_ENTITY_CRM_ACTIVITY_ID_FIELD'),
				'default_value' => '0',
			]),
			new TextField('CRM_TRACE_DATA', [
				'title' => Loc::getMessage('SESSION_ENTITY_CRM_TRACE_DATA_FIELD')
			]),
			new DatetimeField('DATE_CREATE', [
				'title' => Loc::getMessage('SESSION_ENTITY_DATE_CREATE_FIELD'),
				'default_value' => [__CLASS__, 'getCurrentDate'],
			]),
			new DatetimeField('DATE_OPERATOR', [
				'title' => Loc::getMessage('SESSION_ENTITY_DATE_OPERATOR_FIELD_1'),
			]),
			new DatetimeField('DATE_MODIFY', [
				'title' => Loc::getMessage('SESSION_ENTITY_DATE_MODIFY_FIELD'),
				'default_value' => [__CLASS__, 'getCurrentDate'],
			]),
			new DatetimeField('DATE_OPERATOR_ANSWER', [
				'title' => Loc::getMessage('SESSION_ENTITY_DATE_OPERATOR_ANSWER_FIELD_NEW_1'),
			]),
			new DatetimeField('DATE_OPERATOR_CLOSE', [
				'title' => Loc::getMessage('SESSION_ENTITY_DATE_OPERATOR_CLOSE_FIELD_NEW'),
			]),
			new DatetimeField('DATE_FIRST_ANSWER', [
				'title' => Loc::getMessage('SESSION_ENTITY_DATE_FIRST_ANSWER_FIELD_NEW'),
			]),
			new DatetimeField('DATE_LAST_MESSAGE', [
				'title' => Loc::getMessage('SESSION_ENTITY_DATE_LAST_MESSAGE_FIELD'),
			]),
			new DatetimeField('DATE_FIRST_LAST_USER_ACTION', [
				'title' => Loc::getMessage('SESSION_ENTITY_DATE_FIRST_LAST_USER_ACTION_FIELD'),
			]),
			new DatetimeField('DATE_CLOSE', [
				'title' => Loc::getMessage('SESSION_ENTITY_DATE_CLOSE_FIELD'),
			]),
			new DatetimeField('DATE_CLOSE_VOTE'),
			new IntegerField('TIME_BOT', [
				'title' => Loc::getMessage('SESSION_ENTITY_TIME_BOT_FIELD'),
				'default_value' => '0',
			]),
			new IntegerField('TIME_FIRST_ANSWER', [
				'title' => Loc::getMessage('SESSION_ENTITY_TIME_FIRST_ANSWER_FIELD_NEW'),
				'default_value' => '0',
			]),
			new IntegerField('TIME_ANSWER', [
				'title' => Loc::getMessage('SESSION_ENTITY_TIME_ANSWER_FIELD_NEW'),
				'default_value' => '0',
			]),
			new IntegerField('TIME_CLOSE', [
				'title' => Loc::getMessage('SESSION_ENTITY_TIME_CLOSE_FIELD'),
				'default_value' => '0',
			]),
			new IntegerField('TIME_DIALOG', [
				'title' => Loc::getMessage('SESSION_ENTITY_TIME_DIALOG_FIELD'),
				'default_value' => '0',
			]),
			new BooleanField('WAIT_ACTION', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('SESSION_ENTITY_WAIT_ACTION_FIELD'),
				'default_value' => 'N',
			]),
			new BooleanField('WAIT_VOTE', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('SESSION_ENTITY_WAIT_VOTE_FIELD'),
				'default_value' => 'N',
			]),
			new BooleanField('WAIT_ANSWER', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('SESSION_ENTITY_WAIT_ANSWER_FIELD_NEW'),
				'default_value' => 'Y',
			]),
			new BooleanField('CLOSED', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('SESSION_ENTITY_CLOSED_FIELD'),
				'default_value' => 'N',
			]),
			new BooleanField('PAUSE', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('SESSION_ENTITY_PAUSE_FIELD'),
				'default_value' => 'N',
			]),
			new BooleanField('SPAM', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('SESSION_ENTITY_SPAM_FIELD'),
				'default_value' => 'N',
			]),
			new BooleanField('WORKTIME', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('SESSION_ENTITY_WORKTIME_FIELD'),
				'default_value' => 'Y',
			]),
			new BooleanField('SEND_NO_ANSWER_TEXT', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('SESSION_ENTITY_SEND_NO_ANSWER_TEXT_FIELD'),
				'default_value' => 'N',
			]),
			new BooleanField('SEND_NO_WORK_TIME_TEXT', [
				'values' => ['N', 'Y'],
				'title' => Loc::getMessage('SESSION_ENTITY_SEND_NO_ANSWER_TEXT_FIELD'),
				'default_value' => 'N',
			]),
			new TextField('QUEUE_HISTORY', [
				'title' => Loc::getMessage('SESSION_ENTITY_QUEUE_HISTORY_FIELD'),
				'default_value' => [],
				'serialized' => true
			]),
			new TextField('BLOCK_REASON', [
				'title' => Loc::getMessage('SESSION_ENTITY_BLOCK_REASON'),
			]),
			new DatetimeField('BLOCK_DATE', [
				'title' => Loc::getMessage('SESSION_ENTITY_BLOCK_DATE'),
			]),
			new IntegerField('VOTE', [
				'required' => true,
				'title' => Loc::getMessage('SESSION_ENTITY_VOTE_FIELD'),
				'default_value' => '0',
			]),
			new IntegerField('VOTE_HEAD', [
				'required' => true,
				'title' => Loc::getMessage('SESSION_ENTITY_VOTE_HEAD_FIELD'),
				'default_value' => '0',
			]),
			new TextField('COMMENT_HEAD', [
				'title' => Loc::getMessage('SESSION_ENTITY_COMMENT_HEAD_FIELD'),
			]),
			new IntegerField('CATEGORY_ID', [
				'title' => Loc::getMessage('SESSION_ENTITY_CATEGORY_ID_FIELD'),
				'default_value' => '0',
			]),
			new IntegerField('EXTRA_REGISTER', [
				'default_value' => '0',
			]),
			new StringField('EXTRA_USER_LEVEL', [
				'validation' => [__CLASS__, 'validateExtraUserLevel'],
			]),
			new StringField('EXTRA_PORTAL_TYPE', [
				'validation' => [__CLASS__, 'validateExtraPortalType'],
			]),
			new StringField('EXTRA_TARIFF', [
				'validation' => [__CLASS__, 'validateExtraTariff'],
			]),
			new StringField('EXTRA_URL', [
				'validation' => [__CLASS__, 'validateExtraUrl'],
			]),
			new StringField('SEND_FORM', [
				'validation' => [__CLASS__, 'validateSendForm'],
				'default_value' => 'none',
			]),
			new BooleanField('SEND_HISTORY', [
				'values' => ['N', 'Y'],
				'default_value' => 'N',
			]),
			new IntegerField('PARENT_ID', [
				'default_value' => '0',
			]),
			(new Reference(
				'INDEX',
				SessionIndexTable::class,
				Join::on('this.ID', 'ref.SESSION_ID')
			))->configureJoinType('inner'),
			new Reference(
				'CONFIG',
				ConfigTable::class,
				Join::on('this.CONFIG_ID', 'ref.ID')
			),
			new Reference(
				'CHECK',
				SessionCheckTable::class,
				Join::on('this.ID', 'ref.SESSION_ID')
			),
			new Reference(
				'KPI_MESSAGES',
				\Bitrix\ImOpenLines\Model\SessionKpiMessagesTable::class,
				Join::on('this.ID', 'ref.SESSION_ID')
			),
			new Reference(
				'LIVECHAT',
				LivechatTable::class,
				Join::on('this.CONFIG_ID', 'ref.CONFIG_ID')
			),
			new BooleanField('IS_FIRST', [
				'values' => ['N', 'Y'],
			]),
		];

		if (Loader::includeModule('im'))
		{
			$result[] = new Reference(
				'CHAT',
				ChatTable::class,
				Join::on('this.CHAT_ID', 'ref.ID')
			);
		}

		return $result;
	}

	/**
	 * @return string
	 */
	public static function getUfId()
	{
		return 'IMOPENLINES_SESSION';
	}

	/**
	 * Returns selection by entity's primary key without slow fields

	 * @param mixed $id Primary key of the entity
	 * @return Main\ORM\Query\Result
	 */
	public static function getByIdPerformance($id)
	{
		return parent::getByPrimary($id, [
			'select' => self::getSelectFieldsPerformance()
		]);
	}

	/**
	 * Returns fields for select without slow fields
	 *
	 * @param string $prefix
	 * @return string[]
	 */
	public static function getSelectFieldsPerformance($prefix = '')
	{
		$skipList = [];

		$whiteList = [];
		$fields = self::getEntity()->getFields();

		foreach ($fields as $key => $field)
		{
			if (in_array($key, $skipList) || $field instanceof Reference)
			{
				continue;
			}
			if (
				$field instanceof ExpressionField &&
				mb_substr($key, -7) === '_SINGLE'
			)
			{
				$ufMultiName = mb_substr($key, 0, -7);

				if (self::getEntity()->hasField($ufMultiName) && self::getEntity()->getField($ufMultiName) instanceof UserTypeField)
				{
					continue;
				}
			}

			$whiteList[] = $prefix? $prefix.'.'.$key: $key;
		}

		return $whiteList;
	}

	/**
	 * @param array $parameters
	 * @return Main\ORM\Query\Result
	 */
	public static function getList(array $parameters = [])
	{
		if (!empty($parameters['select']) && in_array('CHAT', array_map('mb_strtoupper', $parameters['select'])))
		{
			Loader::includeModule('im');
		}

		return parent::getList($parameters);
	}

	/**
	 * @param Event $event
	 * @return EventResult
	 */
	public static function onAfterAdd(Event $event)
	{
		$id = $event->getParameter("id");
		static::indexRecord($id);
		Statistics\EventHandler::onSessionCreate($event);
		return new EventResult();
	}

	/**
	 * @param Event $event
	 */
	public static function onBeforeUpdate(Event $event)
	{
		Statistics\EventHandler::onSessionBeforeUpdate($event);
	}

	/**
	 * @param Event $event
	 * @return EventResult
	 */
	public static function onAfterUpdate(Event $event)
	{
		$primary = $event->getParameter("id");
		$id = $primary["ID"];
		static::indexRecord($id);
		Statistics\EventHandler::onSessionUpdate($event);
		return new EventResult();
	}

	/**
	 * @param $id
	 */
	public static function indexRecord($id)
	{
		$id = (int)$id;
		if ($id == 0)
		{
			return;
		}

		$select = self::getSelectFieldsPerformance();
		$select['CONFIG_LINE_NAME'] = 'CONFIG.LINE_NAME';

		$record = parent::getByPrimary($id, [
			'select' => $select
		])->fetch();
		if (!is_array($record))
		{
			return;
		}

		SessionIndexTable::merge([
			'SESSION_ID' => $id,
			'SEARCH_CONTENT' => self::generateSearchContent($record)
		]);
	}

	/**
	 * @param array $fields Record as returned by getList
	 * @return string
	 */
	public static function generateSearchContent(array $fields)
	{
		$crmCaption = Crm\Common::generateSearchContent($fields['CRM_ACTIVITY_ID']);

		$userId = [];

		if ($fields['CHAT_ID'] > 0 && $fields['CLOSED'] == 'Y' && Loader::includeModule('im'))
		{
			$userId[$fields['OPERATOR_ID']] = $fields['OPERATOR_ID'];
			$userId[$fields['USER_ID']] = $fields['USER_ID'];

			$transcriptLines = [];
			$cursor = MessageTable::getList([
				'select' => ['MESSAGE', 'AUTHOR_ID'],
				'filter' => [
					'=CHAT_ID' => $fields['CHAT_ID'],
					'>=ID' => $fields['START_ID'],
					'<=ID' => $fields['END_ID'],
				],
			]);
			while ($row = $cursor->fetch())
			{
				if ($row['AUTHOR_ID'] == 0)
				{
					continue;
				}
				$userId[$row['AUTHOR_ID']] = $row['AUTHOR_ID'];
				$transcriptLines[] = $row['MESSAGE'];
			}

			$transcriptLines = implode(" ", $transcriptLines);
			$transcriptLines = Im\Text::removeBbCodes($transcriptLines);
			if (mb_strlen($transcriptLines) > 5000000)
			{
				$transcriptLines = mb_substr($transcriptLines, 0, 5000000);
			}
		}
		else
		{
			$transcriptLines = "";
			$userId[$fields['OPERATOR_ID']] = $fields['OPERATOR_ID'];
			$userId[$fields['USER_ID']] = $fields['USER_ID'];
		}

		$mapBuilderManager = MapBuilder::create();

		if (!empty($userId))
		{
			$mapBuilderManager->addUser($userId);
		}
		if (!empty($crmCaption))
		{
			foreach ($crmCaption as $item)
			{
				$mapBuilderManager->addText($item);
			}
		}
		if (!empty($fields['EXTRA_URL']))
		{
			$mapBuilderManager->addText($fields['EXTRA_URL']);
		}
		if (!empty($fields['ID']))
		{
			$mapBuilderManager->addInteger($fields['ID']);
			$mapBuilderManager->addText('imol|'.$fields['ID']);
		}
		if (!empty($transcriptLines))
		{
			$mapBuilderManager->addText($transcriptLines);
		}

		return $mapBuilderManager->build();
	}

	/**
	 * Returns validators for SOURCE field.
	 *
	 * @return Validator[]
	 */
	public static function validateSource()
	{
		return [
			new LengthValidator(null, 255),
		];
	}

	/**
	 * Returns validators for SOURCE field.
	 *
	 * @return Validator[]
	 */
	public static function validateMode()
	{
		return [
			new LengthValidator(null, 255),
		];
	}

	/**
	 * Returns validators for USER_CODE field.
	 *
	 * @return Validator[]
	 */
	public static function validateUserCode()
	{
		return [
			new LengthValidator(null, 255),
		];
	}

	/**
	 * Returns validators for EXTRA_TARIFF field.
	 *
	 * @return Validator[]
	 */
	public static function validateExtraTariff()
	{
		return [
			new LengthValidator(null, 255),
		];
	}

	/**
	 * Returns validators for EXTRA_USER_LEVEL field.
	 *
	 * @return Validator[]
	 */
	public static function validateExtraUserLevel()
	{
		return [
			new LengthValidator(null, 255),
		];
	}

	/**
	 * Returns validators for EXTRA_PORTAL_TYPE field.
	 *
	 * @return Validator[]
	 */
	public static function validateExtraPortalType()
	{
		return [
			new LengthValidator(null, 255),
		];
	}

	/**
	 * Returns validators for EXTRA_URL field.
	 *
	 * @return Validator[]
	 */
	public static function validateExtraUrl()
	{
		return [
			new LengthValidator(null, 255),
		];
	}

	/**
	 * Returns validators for SEND_FORM field.
	 *
	 * @return Validator[]
	 */
	public static function validateSendForm()
	{
		return [
			new LengthValidator(null, 255),
		];
	}

	/**
	 * Return current date for DATE_CREATE field.
	 *
	 * @return DateTime
	 */
	public static function getCurrentDate()
	{
		return new DateTime();
	}
}
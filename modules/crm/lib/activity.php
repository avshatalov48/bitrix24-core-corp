<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2013 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Crm\Activity\Provider\Tasks\Task;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\TextField;

Loc::loadMessages(__FILE__);

/**
 * Class ActivityTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Activity_Query query()
 * @method static EO_Activity_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Activity_Result getById($id)
 * @method static EO_Activity_Result getList(array $parameters = [])
 * @method static EO_Activity_Entity getEntity()
 * @method static \Bitrix\Crm\EO_Activity createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_Activity_Collection createCollection()
 * @method static \Bitrix\Crm\EO_Activity wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_Activity_Collection wakeUpCollection($rows)
 */
class ActivityTable extends Entity\DataManager
{
	/**
	 * @inheritdoc
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_act';
	}

	public static function getUFId()
	{
		return 'CRM_ACTIVITY';
	}

	/**
	 * @inheritdoc
	 * @return array
	 */
	public static function getMap()
	{
		global $DB;

		$datetimeNull = 'CAST(NULL AS DATETIME)';

		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true
			),
			'TYPE_ID' => array(
				'data_type' => 'integer'
			),
			'PROVIDER_ID' => array(
				'data_type' => 'string'
			),
			'PROVIDER_TYPE_ID' => array(
				'data_type' => 'string'
			),
			'PROVIDER_GROUP_ID' => array(
					'data_type' => 'string'
			),
			'DIRECTION' => array(
				'data_type' => 'integer'
			),
			'IS_MEETING' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s = '.\CCrmActivityType::Meeting.' THEN 1 ELSE 0 END',
					'TYPE_ID'
				),
				'values' => array(0, 1)
			),
			'IS_CALL' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s = '.\CCrmActivityType::Call.' THEN 1 ELSE 0 END',
					'TYPE_ID'
				),
				'values' => array(0, 1)
			),
			'IS_CALL_IN' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s = '.\CCrmActivityType::Call.
					' AND %s = '.\CCrmActivityDirection::Incoming.' THEN 1 ELSE 0 END',
					'TYPE_ID', 'DIRECTION'
				),
				'values' => array(0, 1)
			),
			'IS_CALL_OUT' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s = '.\CCrmActivityType::Call.
					' AND %s = '.\CCrmActivityDirection::Outgoing.' THEN 1 ELSE 0 END',
					'TYPE_ID', 'DIRECTION'
				),
				'values' => array(0, 1)
			),
			'IS_TASK' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %1$s = '.\CCrmActivityType::Task.' THEN 1
					 WHEN %1$s = ' . \CCrmActivityType::Provider . ' AND %2$s = \'' . Task::getId() . '\' THEN 1
					 ELSE 0 END',
					'TYPE_ID', 'PROVIDER_ID'
				),
				'values' => array(0, 1)
			),
			'IS_EMAIL' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s = '.\CCrmActivityType::Email.' THEN 1 ELSE 0 END',
					'TYPE_ID'
				),
				'values' => array(0, 1)
			),
			'IS_EMAIL_IN' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s = '.\CCrmActivityType::Email.
					' AND %s = '.\CCrmActivityDirection::Incoming.' THEN 1 ELSE 0 END',
					'TYPE_ID', 'DIRECTION'
				),
				'values' => array(0, 1)
			),
			'IS_EMAIL_OUT' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s = '.\CCrmActivityType::Email.
					' AND %s = '.\CCrmActivityDirection::Outgoing.' THEN 1 ELSE 0 END',
					'TYPE_ID', 'DIRECTION'
				),
				'values' => array(0, 1)
			),
			'OWNER_ID' => array(
				'data_type' => 'integer'
			),
			'OWNER_TYPE_ID' => array(
				'data_type' => 'integer'
			),
			'ASSOCIATED_ENTITY_ID' => array(
				'data_type' => 'integer'
			),
			'SUBJECT' => array(
				'data_type' => 'string',
				'save_data_modification' => array('\Bitrix\Main\Text\Emoji', 'getSaveModificator'),
				'fetch_data_modification' => array('\Bitrix\Main\Text\Emoji', 'getFetchModificator'),
			),
			'DESCRIPTION' => array(
				'data_type' => 'string',
				'save_data_modification' => array('\Bitrix\Main\Text\Emoji', 'getSaveModificator'),
				'fetch_data_modification' => array('\Bitrix\Main\Text\Emoji', 'getFetchModificator'),
			),
			'DESCRIPTION_TYPE' => array(
				'data_type' => 'integer',
			),
			'COMPLETED' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y')
			),
			'IS_HANDLEABLE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y')
			),
			'RESPONSIBLE_ID' => array(
				'data_type' => 'integer'
			),
			'ASSIGNED_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.RESPONSIBLE_ID' => 'ref.ID')
			),
			'PRIORITY' => array(
				'data_type' => 'integer'
			),
			'NOTIFY_TYPE' => array(
				'data_type' => 'integer'
			),
			'NOTIFY_VALUE' => array(
				'data_type' => 'integer'
			),
			'LOCATION' => array(
				'data_type' => 'string'
			),
			'CREATED' => array(
				'data_type' => 'datetime'
			),
			'DATE_CREATED_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'CREATED'
				)
			),
			'LAST_UPDATED' => array(
				'data_type' => 'datetime'
			),
			'LAST_UPDATED_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'LAST_UPDATED'
				)
			),
			'DATE_FINISHED_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					'CASE WHEN %s = \'Y\' THEN '.$DB->datetimeToDateFunction('%s').' ELSE '.$datetimeNull.' END', 'COMPLETED', 'END_TIME'
				)
			),
			'START_TIME' => array(
				'data_type' => 'datetime'
			),
			'START_TIME_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'START_TIME'
				)
			),
			'END_TIME' => array(
				'data_type' => 'datetime'
			),
			'END_TIME_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'END_TIME'
				)
			),
			'DEADLINE' => array(
				'data_type' => 'datetime'
			),
			'PARENT_ID' => array(
				'data_type' => 'integer'
			),
			'THREAD_ID' => array(
				'data_type' => 'integer'
			),
			'URN' => array(
				'data_type' => 'string'
			),
			'ORIGIN_ID' => array(
				'data_type' => 'string'
			),
			'ORIGINATOR_ID' => array(
				'data_type' => 'string'
			),
			'AUTHOR_ID' => array(
				'data_type' => 'integer'
			),
			'AUTHOR_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.AUTHOR_ID' => 'ref.ID')
			),
			'EDITOR_ID' => array(
				'data_type' => 'integer'
			),
			'EDITOR_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.EDITOR_ID' => 'ref.ID')
			),
			'RESULT_STATUS' => array(
				'data_type' => 'integer'
			),
			'RESULT_STREAM' => array(
				'data_type' => 'integer'
			),
			'RESULT_SOURCE_ID' => array(
				'data_type' => 'string'
			),
			'RESULT_MARK' => array(
				'data_type' => 'integer'
			),
			'RESULT_VALUE' => array(
				'data_type' => 'integer'
			),
			'RESULT_SUM' => array(
				'data_type' => 'float'
			),
			'RESULT_CURRENCY_ID' => array(
				'data_type' => 'string'
			),
			'AUTOCOMPLETE_RULE' => array(
				'data_type' => 'integer'
			),
			'BINDINGS' => array(
				'data_type' => '\Bitrix\Crm\ActivityBindingTable',
				'reference' => array(
					'=this.ID' => 'ref.ACTIVITY_ID'
				),
				'join_type' => 'INNER',
			),
			'ELEMENTS' => array(
				'data_type' => '\Bitrix\Crm\ActivityElementTable',
				'reference' => array(
					'=this.ID' => 'ref.ACTIVITY_ID'
				),
				'join_type' => 'INNER',
			),
			'SEARCH_CONTENT' => array(
				'data_type' => 'string'
			),

			(new ArrayField('SETTINGS'))
				->configureSerializeCallback([self::class, 'serializeSettings'])
				->configureUnserializeCallback([self::class, 'unserializeSettings']),

			(new Entity\StringField('STORAGE_ELEMENT_IDS')),
				// ->configureSerializeCallback([self::class, 'serializeSettings'])
				// ->configureUnserializeCallback([self::class, 'unserializeSettings']),

			new TextField('PROVIDER_PARAMS', [
				'serialized' => true
			]),

			new Entity\IntegerField('STORAGE_TYPE_ID'),
		);
	}

	public static function getFieldCaption($fieldName)
	{
		$result = Loc::getMessage("CRM_ACTIVITY_ENTITY_{$fieldName}_FIELD");
		return is_string($result) ? $result : '';
	}

	public static function serializeSettings($value)
	{
		$value = self::encodeEmoji($value);
		return serialize($value);
	}

	public static function unserializeSettings($value)
	{
		$value = unserialize($value, ['allowed_classes' => false]);

		return self::decodeEmoji($value);
	}

	private static function encodeEmoji($value)
	{
		if (is_array($value))
		{
			foreach ($value as $k=>$v)
			{
				$value[$k] = self::encodeEmoji($v);
			}
		}
		if (is_string($value))
		{
			$value = \Bitrix\Main\Text\Emoji::encode($value);
		}

		return $value;
	}

	private static function decodeEmoji($value)
	{
		if (is_array($value))
		{
			foreach ($value as $k=>$v)
			{
				$value[$k] = self::decodeEmoji($v);
			}
		}
		if (is_string($value))
		{
			$value = \Bitrix\Main\Text\Emoji::decode($value);
		}

		return $value;
	}
}
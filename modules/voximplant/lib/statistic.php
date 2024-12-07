<?php
namespace Bitrix\Voximplant;

use Bitrix\Main\Entity;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Voximplant\Model\Base;
use Bitrix\Voximplant\Model\CallCrmEntityTable;
use Bitrix\Voximplant\Model\StatisticIndexTable;
use Bitrix\Voximplant\Model\TranscriptLineTable;
use Bitrix\Voximplant\Model\TranscriptTable;
use Bitrix\Voximplant\Search\MapBuilder;

Loc::loadMessages(__FILE__);

/**
 * Class StatisticTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ACCOUNT_ID int mandatory
 * <li> APPLICATION_ID int mandatory
 * <li> APPLICATION_NAME string(80) mandatory
 * <li> PORTAL_USER_ID int mandatory
 * <li> PORTAL_NUMBER string(20)
 * <li> PHONE_NUMBER string(20) mandatory
 * <li> INCOMING string(50) mandatory
 * <li> CALL_ID string(255) optional
 * <li> CALL_LOG string(2000) optional
 * <li> CALL_DIRECTION string(255) optional
 * <li> CALL_DURATION int mandatory
 * <li> CALL_START_DATE datetime mandatory
 * <li> CALL_STATUS int optional
 * <li> CALL_RECORD_ID int optional
 * <li> CALL_WEBDAV_ID int optional
 * <li> COST double optional default 0.0000
 * <li> COST_CURRENCY string(50) optional
 * </ul>
 *
 * @package Bitrix\Voximplant
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Statistic_Query query()
 * @method static EO_Statistic_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Statistic_Result getById($id)
 * @method static EO_Statistic_Result getList(array $parameters = [])
 * @method static EO_Statistic_Entity getEntity()
 * @method static \Bitrix\Voximplant\EO_Statistic createObject($setDefaultValues = true)
 * @method static \Bitrix\Voximplant\EO_Statistic_Collection createCollection()
 * @method static \Bitrix\Voximplant\EO_Statistic wakeUpObject($row)
 * @method static \Bitrix\Voximplant\EO_Statistic_Collection wakeUpCollection($rows)
 */

class StatisticTable extends Base
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_voximplant_statistic';
	}

	public static function getMap()
	{
		return array(
			new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage("STATISTIC_ENTITY_ID_FIELD")
			)),
			new Entity\IntegerField('ACCOUNT_ID', array(
				'title' => Loc::getMessage("STATISTIC_ENTITY_ACCOUNT_ID_FIELD")
			)),
			new Entity\IntegerField('APPLICATION_ID'),
			new Entity\StringField('APPLICATION_NAME', array(
				'validation' => function(){return array(new Entity\Validator\Length(null, 80));},
				'title' => Loc::getMessage('STATISTIC_ENTITY_APPLICATION_NAME_FIELD'),
			)),
			new Entity\IntegerField('PORTAL_USER_ID'),
			new Entity\StringField('PORTAL_NUMBER', array(
				'required' => false,
				'validation' => function(){return array(new Entity\Validator\Length(null, 50));},
				'title' => Loc::getMessage('STATISTIC_ENTITY_PORTAL_NUMBER_FIELD_MSGVER_1'),
			)),
			new Entity\StringField('PHONE_NUMBER', array(
				'validation' => function(){return array(new Entity\Validator\Length(null, 20));},
				'title' => Loc::getMessage('STATISTIC_ENTITY_PHONE_NUMBER_FIELD'),
			)),
			new Entity\StringField('INCOMING', array(
				'required' => true,
				'validation' => function(){return array(new Entity\Validator\Length(null, 50));},
				'title' => Loc::getMessage('STATISTIC_ENTITY_INCOMING_FIELD'),
			)),
			new Entity\StringField('CALL_ID', array(
				'required' => false,
				'validation' => function(){return array(new Entity\Validator\Length(null, 255));},
				'title' => Loc::getMessage('STATISTIC_ENTITY_CALL_ID_FIELD'),
			)),
			new Entity\StringField('EXTERNAL_CALL_ID', array(
				'required' => false,
				'validation' => function(){return array(new Entity\Validator\Length(null, 64));},
			)),
			new Entity\StringField('CALL_CATEGORY', array(
				'required' => false,
				'validation' => function(){return array(new Entity\Validator\Length(null, 20));},
				'title' => Loc::getMessage('STATISTIC_ENTITY_CALL_TYPE_FIELD'),
			)),
			new Entity\StringField('CALL_LOG', array(
				'validation' => function(){return array(new Entity\Validator\Length(null, 2000));},
				'title' => Loc::getMessage('STATISTIC_ENTITY_CALL_LOG_FIELD'),
			)),
			new Entity\StringField('CALL_DIRECTION', array(
				'validation' => function(){return array(new Entity\Validator\Length(null, 255));},
				'title' => Loc::getMessage('STATISTIC_ENTITY_CALL_DIRECTION_FIELD'),
			)),
			new Entity\IntegerField('CALL_DURATION', array(
				'required' => false,
				'title' => Loc::getMessage('STATISTIC_ENTITY_CALL_DURATION_FIELD'),
			)),
			new Entity\DatetimeField('CALL_START_DATE', array(
				'required' => true,
				'title' => Loc::getMessage('STATISTIC_ENTITY_CALL_START_DATE_FIELD'),
			)),
			new Entity\IntegerField('CALL_STATUS', array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('STATISTIC_ENTITY_CALL_STATUS_FIELD'),
			)),
			new Entity\IntegerField('CALL_RECORD_ID', array(
				'title' => Loc::getMessage('STATISTIC_ENTITY_CALL_RECORD_ID_FIELD_2'),
			)),
			new Entity\StringField('CALL_RECORD_URL', array(
				'validation' => function(){return array(new Entity\Validator\Length(null, 2000));},
				'title' => Loc::getMessage('STATISTIC_ENTITY_CALL_RECORD_URL_FIELD'),
			)),
			new Entity\IntegerField('CALL_WEBDAV_ID', array(
				'title' => Loc::getMessage('STATISTIC_ENTITY_CALL_WEBDAV_ID_FIELD'),
			)),
			new Entity\IntegerField('CALL_VOTE', array(
				'title' => Loc::getMessage('STATISTIC_ENTITY_CALL_VOTE_FIELD'),
			)),
			new Entity\FloatField('COST', array(
				'title' => Loc::getMessage('STATISTIC_ENTITY_COST_FIELD'),
			)),
			new Entity\StringField('COST_CURRENCY', array(
				'validation' => function(){return array(new Entity\Validator\Length(null, 50));},
				'title' => Loc::getMessage('STATISTIC_ENTITY_COST_CURRENCY_FIELD'),
			)),
			new Entity\StringField('CALL_FAILED_CODE', array(
				'validation' => function(){return array(new Entity\Validator\Length(null, 255));},
				'title' => Loc::getMessage('STATISTIC_ENTITY_CALL_FAILED_CODE_FIELD'),
			)),
			new Entity\StringField('CALL_FAILED_REASON', array(
				'validation' => function(){return array(new Entity\Validator\Length(null, 255));},
				'title' => Loc::getMessage('STATISTIC_ENTITY_CALL_FAILED_REASON_FIELD'),
			)),
			new Entity\StringField('CRM_ENTITY_TYPE'),
			new Entity\IntegerField('CRM_ENTITY_ID'),
			new Entity\IntegerField('CRM_ACTIVITY_ID'),
			new Entity\IntegerField('REST_APP_ID'),
			new Entity\StringField('REST_APP_NAME'),
			new Entity\IntegerField('TRANSCRIPT_ID'),
			new Entity\BooleanField('TRANSCRIPT_PENDING', array(
				'values' => array('N', 'Y'),
				'default_value' => 'N'
			)),
			new Entity\IntegerField('SESSION_ID'),
			new Entity\IntegerField('REDIAL_ATTEMPT'),
			new Entity\TextField('COMMENT'),
			new Entity\IntegerField('RECORD_DURATION'),
			new Entity\ReferenceField(
				'SEARCH_INDEX',
				StatisticIndexTable::getEntity(),
				array("=this.ID" => "ref.STATISTIC_ID"),
				array("join_type" => "LEFT")
			),
			new Entity\ReferenceField(
				"TRANSCRIPT",
				TranscriptTable::getEntity(),
				array("=this.TRANSCRIPT_ID" => "ref.ID"),
				array("join_type" => "LEFT")
			),

			(new OneToMany('CRM_BINDINGS', CallCrmEntityTable::class, 'CALL'))->configureJoinType('left'),

			new \Bitrix\Main\Entity\ExpressionField(
				'HAS_RECORD',
				"CASE WHEN %s IS NULL THEN 'N' ELSE 'Y' END",
				array('CALL_WEBDAV_ID')
			),
			new Entity\ExpressionField('TOTAL_DURATION','SUM(%s)', array('CALL_DURATION')),
			new Entity\ExpressionField('TOTAL_COST','SUM(%s)', array('COST')),
		);
	}

	public static function getByCallId($callId)
	{
		return static::getList(array(
			'filter' => array(
				'=CALL_ID' => $callId
			)
		))->fetch();
	}

	public static function onAfterAdd(Entity\Event $event)
	{
		$id = $event->getParameter("id");
		static::indexRecord($id);
		return new Entity\EventResult();
	}

	public static function onAfterUpdate(Entity\Event $event)
	{
		$primary = $event->getParameter("id");
		$id = $primary["ID"];
		static::indexRecord($id);
		return new Entity\EventResult();
	}

	public static function indexRecord($id)
	{
		$id = (int)$id;
		if($id == 0)
			return;

		$record = static::getById($id)->fetch();
		if(!is_array($record))
			return;

		StatisticIndexTable::merge(array(
			'STATISTIC_ID' => $id,
			'CONTENT' => static::generateSearchContent($record)
		));
	}

	/**
	 * @param array $fields Record as returned by getList
	 * @return string
	 */
	public static function generateSearchContent(array $fields)
	{
		$portalNumber = $fields['PORTAL_NUMBER'];
		if($row = ConfigTable::getBySearchId($portalNumber)->fetch())
		{
			$portalNumber = $row['PHONE_NAME'] == '' ? $portalNumber : $row['PHONE_NAME'];
		}

		if($fields['CRM_ENTITY_TYPE'] != '' && $fields['CRM_ENTITY_ID'] > 0)
			$crmEntityCaption = \CVoxImplantCrmHelper::getEntityCaption($fields['CRM_ENTITY_TYPE'], $fields['CRM_ENTITY_ID']);
		else
			$crmEntityCaption = '';

		$transcriptLines = array();
		if($fields['TRANSCRIPT_ID'] > 0)
		{
			$cursor = \Bitrix\Voximplant\Model\TranscriptLineTable::getList(array(
				'filter' => array('=TRANSCRIPT_ID' => $fields['TRANSCRIPT_ID']),
				'order' => array('START_TIME' => 'ASC')
			));
			while ($row = $cursor->fetch())
			{
				$transcriptLines[] = $row['MESSAGE'];
			}
		}

		$result = MapBuilder::create()
			->addText($portalNumber)
			->addPhone($fields['PHONE_NUMBER'])
			->addText(\CVoxImplantHistory::getStatusText($fields['CALL_FAILED_CODE']))
			->addText(\CVoxImplantHistory::getDirectionText($fields['INCOMING']))
			->addUser($fields['PORTAL_USER_ID'])
			->addText($crmEntityCaption)
			->addText(implode(" ", $transcriptLines))
			->addText($fields['COMMENT'])
			->build();
		return $result;
	}
}
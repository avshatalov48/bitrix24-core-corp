<?php
namespace Bitrix\Timeman\Model;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class AbsenceTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_ID int mandatory
 * <li> TYPE string(255) optional
 * <li> DATE_START datetime optional
 * <li> DATE_FINISH datetime optional
 * <li> TIME_START int optional
 * <li> TIME_FINISH int optional
 * <li> SOURCE_START string(255) optional
 * <li> SOURCE_FINISH string(255) optional
 * <li> DURATION int optional
 * <li> ACTIVE bool optional default 'Y'
 * </ul>
 *
 * @package Bitrix\Timeman
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Absence_Query query()
 * @method static EO_Absence_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Absence_Result getById($id)
 * @method static EO_Absence_Result getList(array $parameters = array())
 * @method static EO_Absence_Entity getEntity()
 * @method static \Bitrix\Timeman\Model\EO_Absence createObject($setDefaultValues = true)
 * @method static \Bitrix\Timeman\Model\EO_Absence_Collection createCollection()
 * @method static \Bitrix\Timeman\Model\EO_Absence wakeUpObject($row)
 * @method static \Bitrix\Timeman\Model\EO_Absence_Collection wakeUpCollection($rows)
 */

class AbsenceTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_timeman_absence';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'ENTRY_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'default_value' => 0,
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'TYPE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateType'),
			),
			'DATE_START' => array(
				'data_type' => 'datetime',
				'default_value' => array(__CLASS__, 'getDateStart'),
			),
			'DATE_FINISH' => array(
				'data_type' => 'datetime',
			),
			'TIME_START' => array(
				'data_type' => 'integer',
				'default_value' => array(__CLASS__, 'getTimeStart'),
			),
			'TIME_FINISH' => array(
				'data_type' => 'integer',
			),
			'DURATION' => array(
				'data_type' => 'integer',
			),
			'SOURCE_START' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateSourceStart'),
			),
			'SOURCE_FINISH' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateSourceFinish'),
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'default_value' => 'Y',
			),
			'REPORT_TYPE' => array(
				'data_type' => 'integer',
				'default_value' => 'NONE',
			),
			'REPORT_TEXT' => array(
				'data_type' => 'text',
			),
			'SYSTEM_TEXT' => array(
				'data_type' => 'text',
			),
			'IP_START' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateIp'),
			),
			'IP_FINISH' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateIp'),
			),
			'REPORT_CALENDAR_ID' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
		);
	}
	/**
	 * Returns validators for TYPE field.
	 *
	 * @return array
	 */
	public static function validateType()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for IP fields.
	 *
	 * @return array
	 */
	public static function validateIp()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
	/**
	 * Returns validators for SOURCE_START field.
	 *
	 * @return array
	 */
	public static function validateSourceStart()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for SOURCE_FINISH field.
	 *
	 * @return array
	 */
	public static function validateSourceFinish()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	public static function getDateStart()
	{
		return new \Bitrix\Main\Type\DateTime();
	}

	public static function getTimeStart()
	{
		$todayStart = new \Bitrix\Main\Type\DateTime((new \Bitrix\Main\Type\DateTime())->format('Y-m-d').' 00:00:00', 'Y-m-d H:i:s');
		$todayNow = new \Bitrix\Main\Type\DateTime();

		return $todayNow->getTimestamp() - $todayStart->getTimestamp();
	}
}
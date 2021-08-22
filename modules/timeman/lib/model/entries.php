<?php
namespace Bitrix\Timeman\Model;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class EntriesTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TIMESTAMP_X datetime mandatory
 * <li> USER_ID int optional
 * <li> MODIFIED_BY int optional
 * <li> ACTIVE bool optional default 'Y'
 * <li> PAUSED bool optional default 'N'
 * <li> DATE_START datetime optional
 * <li> DATE_FINISH datetime optional
 * <li> TIME_START int optional
 * <li> TIME_FINISH int optional
 * <li> DURATION int optional
 * <li> TIME_LEAKS int optional
 * <li> TASKS string optional
 * <li> IP_OPEN string(50) optional
 * <li> IP_CLOSE string(50) optional
 * <li> FORUM_TOPIC_ID int optional
 * <li> LAT_OPEN double optional
 * <li> LON_OPEN double optional
 * <li> LAT_CLOSE double optional
 * <li> LON_CLOSE double optional
 * </ul>
 *
 * @package Bitrix\Timeman
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Entries_Query query()
 * @method static EO_Entries_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Entries_Result getById($id)
 * @method static EO_Entries_Result getList(array $parameters = array())
 * @method static EO_Entries_Entity getEntity()
 * @method static \Bitrix\Timeman\Model\EO_Entries createObject($setDefaultValues = true)
 * @method static \Bitrix\Timeman\Model\EO_Entries_Collection createCollection()
 * @method static \Bitrix\Timeman\Model\EO_Entries wakeUpObject($row)
 * @method static \Bitrix\Timeman\Model\EO_Entries_Collection wakeUpCollection($rows)
 */

class EntriesTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_timeman_entries';
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
			'TIMESTAMP_X' => array(
				'data_type' => 'datetime',
				'required' => true,
			),
			'USER_ID' => array(
				'data_type' => 'integer',
			),
			'MODIFIED_BY' => array(
				'data_type' => 'integer',
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'PAUSED' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'DATE_START' => array(
				'data_type' => 'datetime',
			),
			'DATE_FINISH' => array(
				'data_type' => 'datetime',
			),
			'TIME_START' => array(
				'data_type' => 'integer',
			),
			'TIME_FINISH' => array(
				'data_type' => 'integer',
			),
			'DURATION' => array(
				'data_type' => 'integer',
			),
			'TIME_LEAKS' => array(
				'data_type' => 'integer',
			),
			'TASKS' => array(
				'data_type' => 'text',
			),
			'IP_OPEN' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateIpOpen'),
			),
			'IP_CLOSE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateIpClose'),
			),
			'FORUM_TOPIC_ID' => array(
				'data_type' => 'integer',
			),
			'LAT_OPEN' => array(
				'data_type' => 'float',
			),
			'LON_OPEN' => array(
				'data_type' => 'float',
			),
			'LAT_CLOSE' => array(
				'data_type' => 'float',
			),
			'LON_CLOSE' => array(
				'data_type' => 'float',
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.USER_ID' => 'ref.ID'),
				'join_type' => 'INNER',
			),
		);
	}
	/**
	 * Returns validators for IP_OPEN field.
	 *
	 * @return array
	 */
	public static function validateIpOpen()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
	/**
	 * Returns validators for IP_CLOSE field.
	 *
	 * @return array
	 */
	public static function validateIpClose()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
}
<?php
namespace Bitrix\Imopenlines\Model;

use Bitrix\Main,
	Bitrix\Main\Type\DateTime,
	Bitrix\Main\Localization\Loc;

/**
 * Class TrackerTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> SESSION_ID int mandatory
 * <li> CHAT_ID int mandatory
 * <li> MESSAGE_ID int mandatory
 * <li> USER_ID int mandatory
 * <li> ACTION string(50) optional
 * <li> CRM_ENTITY_TYPE string(50) optional
 * <li> CRM_ENTITY_ID int optional
 * <li> VALUE string(255) optional
 * <li> DATE_CREATE datetime optional
 * </ul>
 *
 * @package Bitrix\Imopenlines
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Tracker_Query query()
 * @method static EO_Tracker_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Tracker_Result getById($id)
 * @method static EO_Tracker_Result getList(array $parameters = array())
 * @method static EO_Tracker_Entity getEntity()
 * @method static \Bitrix\Imopenlines\Model\EO_Tracker createObject($setDefaultValues = true)
 * @method static \Bitrix\Imopenlines\Model\EO_Tracker_Collection createCollection()
 * @method static \Bitrix\Imopenlines\Model\EO_Tracker wakeUpObject($row)
 * @method static \Bitrix\Imopenlines\Model\EO_Tracker_Collection wakeUpCollection($rows)
 */

class TrackerTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_imopenlines_tracker';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('TRACKER_ENTITY_ID_FIELD'),
			],
			'SESSION_ID' => [
				'data_type' => 'integer',
				'title' => Loc::getMessage('TRACKER_ENTITY_SESSION_ID_FIELD'),
			],
			'CHAT_ID' => [
				'data_type' => 'integer',
				'title' => Loc::getMessage('TRACKER_ENTITY_CHAT_ID_FIELD'),
			],
			'MESSAGE_ID' => [
				'data_type' => 'integer',
				'title' => Loc::getMessage('TRACKER_ENTITY_MESSAGE_ID_FIELD'),
			],
			'MESSAGE_ORIGIN_ID' => [
				'data_type' => 'integer',
				'title' => Loc::getMessage('TRACKER_ENTITY_MESSAGE_ORIGIN_ID_FIELD'),
			],
			'USER_ID' => [
				'data_type' => 'integer',
				'title' => Loc::getMessage('TRACKER_ENTITY_USER_ID_FIELD'),
			],
			'TRACK_ID' => [
				'data_type' => 'string',
				'validation' => [__CLASS__, 'validateLength50'],
			],
			'ACTION' => [
				'data_type' => 'string',
				'validation' => [__CLASS__, 'validateLength50'],
				'title' => Loc::getMessage('TRACKER_ENTITY_ACTION_FIELD'),
			],
			'CRM_ENTITY_TYPE' => [
				'data_type' => 'string',
				'validation' => [__CLASS__, 'validateLength50'],
				'title' => Loc::getMessage('TRACKER_ENTITY_CRM_ENTITY_TYPE_FIELD'),
			],
			'CRM_ENTITY_ID' => [
				'data_type' => 'integer',
				'title' => Loc::getMessage('TRACKER_ENTITY_CRM_ENTITY_ID_FIELD'),
			],
			'CRM_CONTACT_ID' => [
				'data_type' => 'integer',
			],
			'CRM_COMPANY_ID' => [
				'data_type' => 'integer',
			],
			'CRM_DEAL_ID' => [
				'data_type' => 'integer',
			],
			'CRM_LEAD_ID' => [
				'data_type' => 'integer',
			],
			'FIELD_ID' => [
				'data_type' => 'string',
				'validation' => [__CLASS__, 'validateValue'],
				'default_value' => 'FM',
				'title' => Loc::getMessage('TRACKER_ENTITY_FIELD_ID_FIELD'),
			],
			'FIELD_TYPE' => [
				'data_type' => 'string',
				'validation' => [__CLASS__, 'validateValue'],
				'title' => Loc::getMessage('TRACKER_ENTITY_FIELD_TYPE_FIELD'),
			],
			'FIELD_VALUE' => [
				'data_type' => 'string',
				'validation' => [__CLASS__, 'validateValue'],
				'title' => Loc::getMessage('TRACKER_ENTITY_FIELD_VALUE_FIELD'),
			],
			'DATE_CREATE' => [
				'data_type' => 'datetime',
				'title' => Loc::getMessage('TRACKER_ENTITY_DATE_CREATE_FIELD'),
				'default_value' => [__CLASS__, 'getCurrentDate'],
			],
		];
	}
	/**
	 * Returns validators for ACTION field.
	 *
	 * @return array
	 */
	public static function validateLength50(): array
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
	/**
	 * Returns validators for VALUE field.
	 *
	 * @return array
	 */
	public static function validateValue(): array
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
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
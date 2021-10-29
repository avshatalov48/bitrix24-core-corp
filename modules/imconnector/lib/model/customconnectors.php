<?php
namespace Bitrix\ImConnector\Model;

use \Bitrix\Main\Entity\TextField,
	\Bitrix\Main\Entity\StringField,
	\Bitrix\Main\Entity\DataManager,
	\Bitrix\Main\Entity\IntegerField,
	\Bitrix\Main\Entity\BooleanField,
	\Bitrix\Main\Entity\Validator\Length;

/**
 * Class CustomConnectorsTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> NAME string(255) optional
 * <li> ICON string
 * <li> COMPONENT string
 * <li> DEL_EXTERNAL_MESSAGES bool optional
 * <li> EDIT_INTERNAL_MESSAGES bool optional
 * <li> DEL_INTERNAL_MESSAGES bool optional
 * <li> NEWSLETTER bool optional
 * <li> NEED_SYSTEM_MESSAGES bool optional
 * <li> NEED_SIGNATURE bool optional
 * <li> CHAT_GROUP bool optional
 * <li> REST_APP_ID int optional
 * </ul>
 *
 * @package Bitrix\ImConnector
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CustomConnectors_Query query()
 * @method static EO_CustomConnectors_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_CustomConnectors_Result getById($id)
 * @method static EO_CustomConnectors_Result getList(array $parameters = array())
 * @method static EO_CustomConnectors_Entity getEntity()
 * @method static \Bitrix\ImConnector\Model\EO_CustomConnectors createObject($setDefaultValues = true)
 * @method static \Bitrix\ImConnector\Model\EO_CustomConnectors_Collection createCollection()
 * @method static \Bitrix\ImConnector\Model\EO_CustomConnectors wakeUpObject($row)
 * @method static \Bitrix\ImConnector\Model\EO_CustomConnectors_Collection wakeUpCollection($rows)
 */
class CustomConnectorsTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_imconnectors_custom_connectors';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			new IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
			)),
			new StringField('ID_CONNECTOR', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateVarChar')
			)),
			new StringField('NAME', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateVarChar')
			)),
			new TextField('ICON', array(
				'serialized' => true,
				'required' => true,
			)),
			new TextField('ICON_DISABLED', array(
				'serialized' => true
			)),
			new TextField('COMPONENT', array(
				'required' => true,
			)),
			new BooleanField('DEL_EXTERNAL_MESSAGES', array(
				'values' => array('N', 'Y'),
				'default_value' => 'Y'
			)),
			new BooleanField('EDIT_INTERNAL_MESSAGES', array(
				'values' => array('N', 'Y'),
				'default_value' => 'Y'
			)),
			new BooleanField('DEL_INTERNAL_MESSAGES', array(
				'values' => array('N', 'Y'),
				'default_value' => 'Y'
			)),
			new BooleanField('NEWSLETTER', array(
				'values' => array('N', 'Y'),
				'default_value' => 'Y'
			)),
			new BooleanField('NEED_SYSTEM_MESSAGES', array(
				'values' => array('N', 'Y'),
				'default_value' => 'Y'
			)),
			new BooleanField('NEED_SIGNATURE', array(
				'values' => array('N', 'Y'),
				'default_value' => 'Y'
			)),
			new BooleanField('CHAT_GROUP', array(
				'values' => array('N', 'Y'),
				'default_value' => 'N'
			)),
			new IntegerField('REST_APP_ID', array(
				'required' => true,
			)),
			new IntegerField('REST_PLACEMENT_ID', array()),
		);
	}

	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	public static function validateVarChar()
	{
		return array(
			new Length(null, 255),
		);
	}
}

<?php
namespace Bitrix\ImConnector\Model;

use \Bitrix\Main\Entity\TextField,
	\Bitrix\Main\Entity\StringField,
	\Bitrix\Main\Entity\DataManager,
	\Bitrix\Main\Entity\IntegerField;

/**
 * Class BotFrameworkTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ID_CHAT string mandatory
 * <li> ID_MESSAGE string
 * <li> VIRTUAL_CONNECTOR string mandatory
 * <li> DATA text
 * </ul>
 *
 * @package Bitrix\ImConnector
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_BotFramework_Query query()
 * @method static EO_BotFramework_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_BotFramework_Result getById($id)
 * @method static EO_BotFramework_Result getList(array $parameters = array())
 * @method static EO_BotFramework_Entity getEntity()
 * @method static \Bitrix\ImConnector\Model\EO_BotFramework createObject($setDefaultValues = true)
 * @method static \Bitrix\ImConnector\Model\EO_BotFramework_Collection createCollection()
 * @method static \Bitrix\ImConnector\Model\EO_BotFramework wakeUpObject($row)
 * @method static \Bitrix\ImConnector\Model\EO_BotFramework_Collection wakeUpCollection($rows)
 */
class BotFrameworkTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_imconnectors_botframework';
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
			new StringField('ID_CHAT', array(
				'required' => true,
			)),
			new StringField('ID_MESSAGE'),
			new StringField('VIRTUAL_CONNECTOR', array(
				'required' => true,
			)),
			new TextField('DATA', array(
				'serialized' => true
			)),
		);
	}
}
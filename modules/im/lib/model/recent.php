<?php
namespace Bitrix\Im\Model;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class RecentTable
 *
 * Fields:
 * <ul>
 * <li> USER_ID int mandatory
 * <li> ITEM_TYPE string(1) mandatory default 'P'
 * <li> ITEM_ID int mandatory
 * <li> ITEM_MID int mandatory
 * </ul>
 *
 * @package Bitrix\Im
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Recent_Query query()
 * @method static EO_Recent_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Recent_Result getById($id)
 * @method static EO_Recent_Result getList(array $parameters = array())
 * @method static EO_Recent_Entity getEntity()
 * @method static \Bitrix\Im\Model\EO_Recent createObject($setDefaultValues = true)
 * @method static \Bitrix\Im\Model\EO_Recent_Collection createCollection()
 * @method static \Bitrix\Im\Model\EO_Recent wakeUpObject($row)
 * @method static \Bitrix\Im\Model\EO_Recent_Collection wakeUpCollection($rows)
 */

class RecentTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_im_recent';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'USER_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'title' => Loc::getMessage('RECENT_ENTITY_USER_ID_FIELD'),
			),
			'ITEM_TYPE' => array(
				'data_type' => 'string',
				'primary' => true,
				'validation' => array(__CLASS__, 'validateItemType'),
				'title' => Loc::getMessage('RECENT_ENTITY_ITEM_TYPE_FIELD'),
			),
			'ITEM_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'title' => Loc::getMessage('RECENT_ENTITY_ITEM_ID_FIELD'),
			),
			'ITEM_MID' => array(
				'data_type' => 'integer',
				'default_value' => 0,
				'title' => Loc::getMessage('RECENT_ENTITY_ITEM_MID_FIELD'),
			),
			'ITEM_CID' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'ITEM_RID' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'ITEM_OLID' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'PINNED' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'default_value' => 'N',
			),
			'UNREAD' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'default_value' => 'N',
			),
			'DATE_MESSAGE' => array(
				'data_type' => 'datetime',
				'required' => true,
				'default_value' => array(__CLASS__, 'getCurrentDate'),
			),
			'DATE_UPDATE' => array(
				'data_type' => 'datetime',
				'required' => true,
				'default_value' => array(__CLASS__, 'getCurrentDate'),
			),
			'RELATION' => array(
				'data_type' => 'Bitrix\Im\Model\RelationTable',
				'reference' => array('=this.ITEM_RID' => 'ref.ID'),
				'join_type' => 'LEFT',
			),
			'CHAT' => array(
				'data_type' => 'Bitrix\Im\Model\ChatTable',
				'reference' => array('=this.ITEM_CID' => 'ref.ID'),
				'join_type' => 'LEFT',
			),
			'MESSAGE' => array(
				'data_type' => 'Bitrix\Im\Model\MessageTable',
				'reference' => array('=this.ITEM_MID' => 'ref.ID'),
				'join_type' => 'LEFT',
			),
			'MESSAGE_UUID' => array(
				'data_type' => 'Bitrix\Im\Model\MessageUuidTable',
				'reference' => array('=this.ITEM_MID' => 'ref.MESSAGE_ID'),
				'join_type' => 'LEFT',
			),
		);
	}

	/**
	 * Returns validators for ITEM_TYPE field.
	 *
	 * @return array
	 */
	public static function validateItemType()
	{
		return array(
			new Main\Entity\Validator\Length(null, 1),
		);
	}

	/**
	 * Return current date for DATE_CREATE field.
	 *
	 * @return \Bitrix\Main\Type\DateTime
	 */
	public static function getCurrentDate()
	{
		return new \Bitrix\Main\Type\DateTime();
	}
}
<?php
namespace Bitrix\Imopenlines\Model;

use \Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Entity\DataManager,
	\Bitrix\Main\Entity\Validator\Length;

use \Bitrix\Main\Entity\TextField,
	\Bitrix\Main\Entity\StringField,
	\Bitrix\Main\Entity\IntegerField,
	\Bitrix\Main\Entity\BooleanField,
	\Bitrix\Main\Entity\ReferenceField;
Loc::loadMessages(__FILE__);

/**
 * Class LivechatTable
 *
 * Fields:
 * <ul>
 * <li> CONFIG_ID int mandatory
 * <li> URL_CODE string(255) optional
 * <li> URL_CODE_ID int optional
 * <li> URL_CODE_PUBLIC string(255) optional
 * <li> URL_CODE_PUBLIC_ID int optional
 * <li> TEMPLATE_ID string(255) optional
 * <li> BACKGROUND_IMAGE int optional
 * <li> CSS_ACTIVE bool optional default 'N'
 * <li> CSS_PATH string(255) optional
 * <li> CSS_TEXT string optional
 * <li> COPYRIGHT_REMOVED bool optional default 'N'
 * <li> CACHE_WIDGET_ID int optional
 * <li> CACHE_BUTTON_ID int optional
 * <li> PHONE_CODE string(255) optional
 * </ul>
 *
 * @package Bitrix\Imopenlines
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Livechat_Query query()
 * @method static EO_Livechat_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Livechat_Result getById($id)
 * @method static EO_Livechat_Result getList(array $parameters = array())
 * @method static EO_Livechat_Entity getEntity()
 * @method static \Bitrix\Imopenlines\Model\EO_Livechat createObject($setDefaultValues = true)
 * @method static \Bitrix\Imopenlines\Model\EO_Livechat_Collection createCollection()
 * @method static \Bitrix\Imopenlines\Model\EO_Livechat wakeUpObject($row)
 * @method static \Bitrix\Imopenlines\Model\EO_Livechat_Collection wakeUpCollection($rows)
 */

class LivechatTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_imopenlines_livechat';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			new IntegerField('CONFIG_ID', array(
				'primary' => true,
				'title' => Loc::getMessage('LIVECHAT_ENTITY_CONFIG_ID_FIELD'),
			)),
			new StringField('URL_CODE', array(
				'validation' => array(__CLASS__, 'validateUrlCode'),
				'title' => Loc::getMessage('LIVECHAT_ENTITY_URL_CODE_FIELD'),
			)),
			new IntegerField('URL_CODE_ID', array(
				'title' => Loc::getMessage('LIVECHAT_ENTITY_URL_CODE_ID_FIELD'),
			)),
			new StringField('URL_CODE_PUBLIC', array(
				'validation' => array(__CLASS__, 'validateUrlCodePublic'),
				'title' => Loc::getMessage('LIVECHAT_ENTITY_URL_CODE_PUBLIC_FIELD'),
			)),
			new IntegerField('URL_CODE_PUBLIC_ID', array(
				'title' => Loc::getMessage('LIVECHAT_ENTITY_URL_CODE_PUBLIC_ID_FIELD'),
			)),
			new StringField('TEMPLATE_ID', array(
				'validation' => array(__CLASS__, 'validateTemplateId'),
				'title' => Loc::getMessage('LIVECHAT_ENTITY_TEMPLATE_ID_FIELD'),
				'default_value' => 'color',
			)),
			new IntegerField('BACKGROUND_IMAGE', array(
				'title' => Loc::getMessage('LIVECHAT_ENTITY_BACKGROUND_IMAGE_FIELD'),
				'default_value' => '0',
			)),
			new BooleanField('CSS_ACTIVE', array(
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('LIVECHAT_ENTITY_CSS_ACTIVE_FIELD'),
				'default_value' => 'N',
			)),
			new StringField('CSS_PATH', array(
				'validation' => array(__CLASS__, 'validateCssPath'),
				'title' => Loc::getMessage('LIVECHAT_ENTITY_CSS_PATH_FIELD'),
			)),
			new TextField('CSS_TEXT', array(
				'title' => Loc::getMessage('LIVECHAT_ENTITY_CSS_TEXT_FIELD'),
			)),
			new BooleanField('COPYRIGHT_REMOVED', array(
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('LIVECHAT_ENTITY_COPYRIGHT_REMOVED_FIELD'),
				'default_value' => 'N',
			)),
			new ReferenceField('CONFIG',
				'Bitrix\ImOpenLines\Model\Config',
				array('=this.CONFIG_ID' => 'ref.ID')
			),
			new IntegerField('CACHE_WIDGET_ID', array(
				'title' => Loc::getMessage('LIVECHAT_ENTITY_CACHE_WIDGET_ID_FIELD'),
			)),
			new IntegerField('CACHE_BUTTON_ID', array(
				'title' => Loc::getMessage('LIVECHAT_ENTITY_CACHE_BUTTON_ID_FIELD'),
			)),
			new StringField('PHONE_CODE', array(
				'validation' => array(__CLASS__, 'validatePhoneCode'),
				'title' => Loc::getMessage('LIVECHAT_ENTITY_PHONE_CODE_FIELD'),
			)),
			new TextField('TEXT_PHRASES', array(
				'title' => Loc::getMessage('LIVECHAT_ENTITY_TEXT_PHRASES_FIELD'),
				'serialized' => true
			)),
			new BooleanField('SHOW_SESSION_ID', array(
				'values' => array('N', 'Y'),
				'default_value' => 'N',
			)),
		);
	}
	/**
	 * Returns validators for URL_CODE field.
	 *
	 * @return array
	 */
	public static function validateUrlCode()
	{
		return array(
			new Length(null, 255),
		);
	}
	/**
	 * Returns validators for URL_CODE_PUBLIC field.
	 *
	 * @return array
	 */
	public static function validateUrlCodePublic()
	{
		return array(
			new Length(null, 255),
		);
	}
	/**
	 * Returns validators for TEMPLATE_ID field.
	 *
	 * @return array
	 */
	public static function validateTemplateId()
	{
		return array(
			new Length(null, 255),
		);
	}
	/**
	 * Returns validators for CSS_PATH field.
	 *
	 * @return array
	 */
	public static function validateCssPath()
	{
		return array(
			new Length(null, 255),
		);
	}
	/**
	 * Returns validators for PHONE_CODE field.
	 *
	 * @return array
	 */
	public static function validatePhoneCode()
	{
		return array(
			new Length(null, 255),
		);
	}
}
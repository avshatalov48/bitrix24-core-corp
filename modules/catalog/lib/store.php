<?php

namespace Bitrix\Catalog;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class StoreTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TITLE string(75) optional
 * <li> ACTIVE bool optional default 'Y'
 * <li> ADDRESS string(245) mandatory
 * <li> DESCRIPTION string optional
 * <li> GPS_N string(15) optional
 * <li> GPS_S string(15) optional
 * <li> IMAGE_ID string(45) optional
 * <li> LOCATION_ID int optional
 * <li> DATE_MODIFY datetime mandatory default 'CURRENT_TIMESTAMP'
 * <li> DATE_CREATE datetime optional
 * <li> USER_ID int optional
 * <li> MODIFIED_BY int optional
 * <li> PHONE string(45) optional
 * <li> SCHEDULE string(255) optional
 * <li> XML_ID string(255) optional
 * <li> SORT int optional default 100
 * <li> EMAIL string(255) optional
 * <li> ISSUING_CENTER bool optional default 'Y'
 * <li> SHIPPING_CENTER bool optional default 'Y'
 * <li> SITE_ID string(2) optional
 * <li> CODE string(255) optional
 * </ul>
 *
 * @package Bitrix\Catalog
 **/
class StoreTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_catalog_store';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('STORE_ENTITY_ID_FIELD')
			)),
			'TITLE' => new Main\Entity\StringField('TITLE', array(
				'validation' => array(__CLASS__, 'validateTitle'),
				'title' => Loc::getMessage('STORE_ENTITY_TITLE_FIELD')
			)),
			'ACTIVE' => new Main\Entity\BooleanField('ACTIVE', array(
				'values' => array('N', 'Y'),
				'default_value' => 'Y',
				'title' => Loc::getMessage('STORE_ENTITY_ACTIVE_FIELD')
			)),
			'ADDRESS' => new Main\Entity\StringField('ADDRESS', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateAddress'),
				'title' => Loc::getMessage('STORE_ENTITY_ADDRESS_FIELD')
			)),
			'DESCRIPTION' => new Main\Entity\TextField('DESCRIPTION', array(
				'title' => Loc::getMessage('STORE_ENTITY_DESCRIPTION_FIELD')
			)),
			'GPS_N' => new Main\Entity\StringField('GPS_N', array(
				'validation' => array(__CLASS__, 'validateGpsN'),
				'title' => Loc::getMessage('STORE_ENTITY_GPS_N_FIELD')
			)),
			'GPS_S' => new Main\Entity\StringField('GPS_S', array(
				'validation' => array(__CLASS__, 'validateGpsS'),
				'title' => Loc::getMessage('STORE_ENTITY_GPS_S_FIELD')
			)),
			'IMAGE_ID' => new Main\Entity\StringField('IMAGE_ID', array(
				'validation' => array(__CLASS__, 'validateImageId'),
				'title' => Loc::getMessage('STORE_ENTITY_IMAGE_ID_FIELD')
			)),
			'LOCATION_ID' => new Main\Entity\IntegerField('LOCATION_ID', array(
				'title' => Loc::getMessage('STORE_ENTITY_LOCATION_ID_FIELD')
			)),
			'DATE_MODIFY' => new Main\Entity\DatetimeField('DATE_MODIFY', array(
				'default_value' => function(){ return new Main\Type\DateTime(); },
				'title' => Loc::getMessage('STORE_ENTITY_DATE_MODIFY_FIELD')
			)),
			'DATE_CREATE' => new Main\Entity\DatetimeField('DATE_CREATE', array(
				'default_value' => function(){ return new Main\Type\DateTime(); },
				'title' => Loc::getMessage('STORE_ENTITY_DATE_CREATE_FIELD')
			)),
			'USER_ID' => new Main\Entity\IntegerField('USER_ID', array(
				'default_value' => null,
				'title' => Loc::getMessage('STORE_ENTITY_USER_ID_FIELD')
			)),
			'MODIFIED_BY' => new Main\Entity\IntegerField('MODIFIED_BY', array(
				'default_value' => null,
				'title' => Loc::getMessage('STORE_ENTITY_MODIFIED_BY_FIELD')
			)),
			'PHONE' => new Main\Entity\StringField('PHONE', array(
				'validation' => array(__CLASS__, 'validatePhone'),
				'title' => Loc::getMessage('STORE_ENTITY_PHONE_FIELD')
			)),
			'SCHEDULE' => new Main\Entity\StringField('SCHEDULE', array(
				'validation' => array(__CLASS__, 'validateSchedule'),
				'title' => Loc::getMessage('STORE_ENTITY_SCHEDULE_FIELD')
			)),
			'XML_ID' => new Main\Entity\StringField('XML_ID', array(
				'validation' => array(__CLASS__, 'validateXmlId'),
				'title' => Loc::getMessage('STORE_ENTITY_XML_ID_FIELD')
			)),
			'SORT' => new Main\Entity\IntegerField('SORT', array(
				'default_value' => 100,
				'title' => Loc::getMessage('STORE_ENTITY_SORT_FIELD')
			)),
			'EMAIL' => new Main\Entity\StringField('EMAIL', array(
				'validation' => array(__CLASS__, 'validateEmail'),
				'title' => Loc::getMessage('STORE_ENTITY_EMAIL_FIELD')
			)),
			'ISSUING_CENTER' => new Main\Entity\BooleanField('ISSUING_CENTER', array(
				'values' => array('N', 'Y'),
				'default_value' => 'Y',
				'title' => Loc::getMessage('STORE_ENTITY_ISSUING_CENTER_FIELD')
			)),
			'SHIPPING_CENTER' => new Main\Entity\BooleanField('SHIPPING_CENTER', array(
				'values' => array('N', 'Y'),
				'default_value' => 'Y',
				'title' => Loc::getMessage('STORE_ENTITY_SHIPPING_CENTER_FIELD')
			)),
			'SITE_ID' => new Main\Entity\StringField('SITE_ID', array(
				'validation' => array(__CLASS__, 'validateSiteId'),
				'title' => Loc::getMessage('STORE_ENTITY_SITE_ID_FIELD')
			)),
			'CODE' => new Main\Entity\StringField('CODE', array(
				'validation' => array(__CLASS__, 'validateCode'),
				'title' => Loc::getMessage('STORE_ENTITY_CODE_FIELD')
			))
		);
	}

	/**
	 * Return uf identifier.
	 *
	 * @return string
	 */
	public static function getUfId()
	{
		return 'CAT_STORE';
	}

	/**
	 * Returns validators for TITLE field.
	 *
	 * @return array
	 */
	public static function validateTitle()
	{
		return array(
			new Main\Entity\Validator\Length(null, 75),
		);
	}

	/**
	 * Returns validators for ADDRESS field.
	 *
	 * @return array
	 */
	public static function validateAddress()
	{
		return array(
			new Main\Entity\Validator\Length(null, 245),
		);
	}

	/**
	 * Returns validators for GPS_N field.
	 *
	 * @return array
	 */
	public static function validateGpsN()
	{
		return array(
			new Main\Entity\Validator\Length(null, 15),
		);
	}

	/**
	 * Returns validators for GPS_S field.
	 *
	 * @return array
	 */
	public static function validateGpsS()
	{
		return array(
			new Main\Entity\Validator\Length(null, 15),
		);
	}

	/**
	 * Returns validators for IMAGE_ID field.
	 *
	 * @return array
	 */
	public static function validateImageId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 45),
		);
	}

	/**
	 * Returns validators for PHONE field.
	 *
	 * @return array
	 */
	public static function validatePhone()
	{
		return array(
			new Main\Entity\Validator\Length(null, 45),
		);
	}

	/**
	 * Returns validators for SCHEDULE field.
	 *
	 * @return array
	 */
	public static function validateSchedule()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for XML_ID field.
	 *
	 * @return array
	 */
	public static function validateXmlId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for EMAIL field.
	 *
	 * @return array
	 */
	public static function validateEmail()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for SITE_ID field.
	 *
	 * @return array
	 */
	public static function validateSiteId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 2),
		);
	}

	/**
	 * Returns validators for CODE field.
	 *
	 * @return array
	 */
	public static function validateCode()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
}
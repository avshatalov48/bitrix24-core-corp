<?php
namespace Bitrix\Seo\Adv;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

/**
 * Class AutologTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ENGINE_ID int mandatory
 * <li> TIMESTAMP_X datetime mandatory default 'CURRENT_TIMESTAMP'
 * <li> CAMPAIGN_ID int mandatory
 * <li> CAMPAIGN_XML_ID string(255) mandatory
 * <li> BANNER_ID int mandatory
 * <li> BANNER_XML_ID string(255) mandatory
 * <li> CAUSE_CODE int optional
 * <li> SUCCESS bool optional
 * </ul>
 *
 * @package Bitrix\Seo
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Autolog_Query query()
 * @method static EO_Autolog_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Autolog_Result getById($id)
 * @method static EO_Autolog_Result getList(array $parameters = array())
 * @method static EO_Autolog_Entity getEntity()
 * @method static \Bitrix\Seo\Adv\EO_Autolog createObject($setDefaultValues = true)
 * @method static \Bitrix\Seo\Adv\EO_Autolog_Collection createCollection()
 * @method static \Bitrix\Seo\Adv\EO_Autolog wakeUpObject($row)
 * @method static \Bitrix\Seo\Adv\EO_Autolog_Collection wakeUpCollection($rows)
 */

class AutologTable extends Entity\DataManager
{
	const CODE_QUANTITY_ON = 1;
	const CODE_QUANTITY_OFF = 2;

	const SUCCESS = 'Y';
	const FAILURE = 'N';

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_seo_adv_autolog';
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
			'ENGINE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'TIMESTAMP_X' => array(
				'data_type' => 'datetime',
				'required' => true,
			),
			'CAMPAIGN_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'CAMPAIGN_XML_ID' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'BANNER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('ADV_AUTOLOG_ENTITY_BANNER_ID_FIELD'),
			),
			'BANNER_XML_ID' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'CAUSE_CODE' => array(
				'data_type' => 'integer',
			),
			'SUCCESS' => array(
				'data_type' => 'boolean',
				'values' => array(static::FAILURE, static::SUCCESS),
			),
		);
	}

	public static function onBeforeAdd(Entity\Event $event)
	{
		$result = new Entity\EventResult();
		$result->modifyFields(array("TIMESTAMP_X" => new DateTime()));
		return $result;
	}
}

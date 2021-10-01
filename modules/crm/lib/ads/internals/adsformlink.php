<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\Ads\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\WebForm\Helper;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

/**
 * Class AdsFormLinkTable.
 * @package Bitrix\Crm\Ads\Internals
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_AdsFormLink_Query query()
 * @method static EO_AdsFormLink_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_AdsFormLink_Result getById($id)
 * @method static EO_AdsFormLink_Result getList(array $parameters = array())
 * @method static EO_AdsFormLink_Entity getEntity()
 * @method static \Bitrix\Crm\Ads\Internals\EO_AdsFormLink createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Ads\Internals\EO_AdsFormLink_Collection createCollection()
 * @method static \Bitrix\Crm\Ads\Internals\EO_AdsFormLink wakeUpObject($row)
 * @method static \Bitrix\Crm\Ads\Internals\EO_AdsFormLink_Collection wakeUpCollection($rows)
 */
class AdsFormLinkTable extends Entity\DataManager
{
	const LINK_DIRECTION_EXPORT = 0;
	const LINK_DIRECTION_IMPORT = 1;

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_ads_form_link';
	}

	/**
	 * Get map.
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
			'DATE_INSERT' => array(
				'data_type' => 'datetime',
				'default_value' => new DateTime(),
			),

			'WEBFORM_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'LINK_DIRECTION' => array(
				'data_type' => 'integer',
				'required' => true,
			),

			'ADS_TYPE' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'ADS_ACCOUNT_ID' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'ADS_FORM_ID' => array(
				'data_type' => 'string',
				'required' => true,
			),

			'ADS_ACCOUNT_NAME' => array(
				'data_type' => 'string',
			),
			'ADS_FORM_NAME' => array(
				'data_type' => 'string',
			)
		);
	}
}

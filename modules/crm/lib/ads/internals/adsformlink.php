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

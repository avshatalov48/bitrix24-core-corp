<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale\Location;

/**
 * Class SiteLocationTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SiteLocation_Query query()
 * @method static EO_SiteLocation_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_SiteLocation_Result getById($id)
 * @method static EO_SiteLocation_Result getList(array $parameters = [])
 * @method static EO_SiteLocation_Entity getEntity()
 * @method static \Bitrix\Sale\Location\EO_SiteLocation createObject($setDefaultValues = true)
 * @method static \Bitrix\Sale\Location\EO_SiteLocation_Collection createCollection()
 * @method static \Bitrix\Sale\Location\EO_SiteLocation wakeUpObject($row)
 * @method static \Bitrix\Sale\Location\EO_SiteLocation_Collection wakeUpCollection($rows)
 */
class SiteLocationTable extends Connector
{
	const ALL_SITES = '*';

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_loc_2site';
	}

	public static function getLinkField()
	{
		return 'SITE_ID';
	}

	public static function getTargetEntityName()
	{
		return 'Bitrix\Main\Site';
	}

	public static function getUseLinkTracking()
	{
		return true;
	}

	public static function getTargetEntityPrimaryField()
	{
		return 'LID';
	}

	public static function onAfterModifiy()
	{
		// todo: re-generate index here later

		Search\Finder::setIndexInvalid();
	}

	public static function getMap()
	{
		return array(

			'SITE_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'primary' => true
			),
			'LOCATION_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'primary' => true
			),
			'LOCATION_TYPE' => array(
				'data_type' => 'string',
				'default_value' => self::DB_LOCATION_FLAG,
				'required' => true,
				'primary' => true
			),

			// virtual
			'LOCATION' => array(
				'data_type' => '\Bitrix\Sale\Location\Location',
				'reference' => array(
					'=this.LOCATION_ID' => 'ref.ID',
					'=this.LOCATION_TYPE' => array('?', self::DB_LOCATION_FLAG)
				),
				'join_type' => 'inner'
			),
			'GROUP' => array(
				'data_type' => '\Bitrix\Sale\Location\Group',
				'reference' => array(
					'=this.LOCATION_ID' => 'ref.ID',
					'=this.LOCATION_TYPE' => array('?', self::DB_GROUP_FLAG)
				)
			),

			'SITE' => array(
				'data_type' => '\Bitrix\Main\Site',
				'reference' => array(
					'=this.SITE_ID' => 'ref.LID'
				)
			),

		);
	}
}


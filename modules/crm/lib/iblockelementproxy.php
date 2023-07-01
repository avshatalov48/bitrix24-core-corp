<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class IBlockElementProxyTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_iblock_element';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'IBLOCK_ID' => array(
				'data_type' => 'integer'
			),
			'NAME' => array(
				'data_type' => 'string'
			)
		);
	}
}

/**
 * Class IBlockElementGrcProxyTable
 * Created only for grouping deal products in report (please see CCrmReportHelper::getGrcColumns)
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_IBlockElementGrcProxy_Query query()
 * @method static EO_IBlockElementGrcProxy_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_IBlockElementGrcProxy_Result getById($id)
 * @method static EO_IBlockElementGrcProxy_Result getList(array $parameters = [])
 * @method static EO_IBlockElementGrcProxy_Entity getEntity()
 * @method static \Bitrix\Crm\EO_IBlockElementGrcProxy createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_IBlockElementGrcProxy_Collection createCollection()
 * @method static \Bitrix\Crm\EO_IBlockElementGrcProxy wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_IBlockElementGrcProxy_Collection wakeUpCollection($rows)
 */
class IBlockElementGrcProxyTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_iblock_element';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'NAME' => array(
				'data_type' => 'string'
			)
		);
	}
}

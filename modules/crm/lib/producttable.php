<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Main\Entity;

/**
 * Class ProductTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Product_Query query()
 * @method static EO_Product_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Product_Result getById($id)
 * @method static EO_Product_Result getList(array $parameters = [])
 * @method static EO_Product_Entity getEntity()
 * @method static \Bitrix\Crm\EO_Product createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_Product_Collection createCollection()
 * @method static \Bitrix\Crm\EO_Product wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_Product_Collection wakeUpCollection($rows)
 */
class ProductTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_product';
	}

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'IBLOCK_ELEMENT' => array(
				'data_type' => 'IBlockElementProxy',
				'reference' => array('=this.ID' => 'ref.ID')
			),
			'IBLOCK_ELEMENT_GRC' => array(
				'data_type' => 'IBlockElementGrcProxy',
				'reference' => array('=this.ID' => 'ref.ID')
			)
		);
	}
}

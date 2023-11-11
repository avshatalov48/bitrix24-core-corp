<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2013-2013 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class InvoiceStUtsTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_InvoiceStUts_Query query()
 * @method static EO_InvoiceStUts_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_InvoiceStUts_Result getById($id)
 * @method static EO_InvoiceStUts_Result getList(array $parameters = [])
 * @method static EO_InvoiceStUts_Entity getEntity()
 * @method static \Bitrix\Crm\EO_InvoiceStUts createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_InvoiceStUts_Collection createCollection()
 * @method static \Bitrix\Crm\EO_InvoiceStUts wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_InvoiceStUts_Collection wakeUpCollection($rows)
 */
class InvoiceStUtsTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_uts_crm_invoice';
	}

	public static function getMap()
	{
		global $DB;

		return array(
			'VALUE_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'UF_DEAL_ID' => array(
				'data_type' => 'integer'
			),
			'UF_COMPANY_ID' => array(
				'data_type' => 'integer'
			),
			'UF_CONTACT_ID' => array(
				'data_type' => 'integer'
			),
			'UF_MYCOMPANY_ID' => array(
				'data_type' => 'integer'
			),
			'DEAL_BY' => array(
				'data_type' => 'Deal',
				'reference' => array('=this.UF_DEAL_ID' => 'ref.ID')
			),
			'CONTACT_BY' => array(
				'data_type' => 'Contact',
				'reference' => array('=this.UF_CONTACT_ID' => 'ref.ID')
			),
			'COMPANY_BY' => array(
				'data_type' => 'Company',
				'reference' => array('=this.UF_COMPANY_ID' => 'ref.ID')
			)
		);
	}
}

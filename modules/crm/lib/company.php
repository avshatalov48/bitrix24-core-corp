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

class CompanyTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_company';
	}

	public static function getUFId()
	{
		return 'CRM_COMPANY';
	}

	public static function getMap()
	{
		global $DB;

		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'TITLE' => array(
				'data_type' => 'string'
			),
			'COMPANY_TYPE' => array(
				'data_type' => 'string'
			),
			'COMPANY_TYPE_BY' => array(
				'data_type' => 'Status',
				'reference' => array(
					'=this.COMPANY_TYPE' => 'ref.STATUS_ID',
					'=ref.ENTITY_ID' => array('?', 'COMPANY_TYPE')
				)
			),
			'INDUSTRY' => array(
				'data_type' => 'string'
			),
			'INDUSTRY_BY' => array(
				'data_type' => 'Status',
				'reference' => array(
					'=this.INDUSTRY' => 'ref.STATUS_ID',
					'=ref.ENTITY_ID' => array('?', 'INDUSTRY')
				)
			),
			'EMPLOYEES' => array(
				'data_type' => 'string'
			),
			'EMPLOYEES_BY' => array(
				'data_type' => 'Status',
				'reference' => array(
					'=this.EMPLOYEES' => 'ref.STATUS_ID',
					'=ref.ENTITY_ID' => array('?', 'EMPLOYEES')
				)
			),
			'REVENUE' => array(
				'data_type' => 'string'
			),
//			'REVENUE_BY' => array( // FOR COMPATIBILITY ONLY
//				'data_type' => 'CrmStatus',
//				'reference' => array('=this.REVENUE' => 'ref.STATUS_ID')
//			),
			'CURRENCY_ID' => array(
				'data_type' => 'string'
			),
//			'CURRENCY_BY' => array(
//				'data_type' => 'CrmStatus',
//				'reference' => array('CURRENCY_ID', 'STATUS_ID')
//			),
			'COMMENTS' => array(
				'data_type' => 'string'
			),
			'ADDRESS' => array(
				'data_type' => 'string'
			),
			'ADDRESS_LEGAL' => array(
				'data_type' => 'string'
			),
			'BANKING_DETAILS' => array(
				'data_type' => 'string'
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime'
			),
			'DATE_MODIFY' => array(
				'data_type' => 'datetime'
			),
			'ASSIGNED_BY_ID' => array(
				'data_type' => 'integer'
			),
			'ASSIGNED_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.ASSIGNED_BY_ID' => 'ref.ID')
			),
			'CREATED_BY_ID' => array(
				'data_type' => 'integer'
			),
			'CREATED_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.CREATED_BY_ID' => 'ref.ID')
			),
			'MODIFY_BY_ID' => array(
				'data_type' => 'integer'
			),
			'MODIFY_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.MODIFY_BY_ID' => 'ref.ID')
			),
			'EVENT_RELATION' => array(
				'data_type' => 'EventRelations',
				'reference' => array('=this.ID' => 'ref.ENTITY_ID')
			),
			'LEAD_ID' => array(
				'data_type' => 'integer'
			),
			'IS_MY_COMPANY' => array(
				'data_type' => 'string'
			),
			'HAS_EMAIL' => array(
				'data_type' => 'boolean',
				'default_value' => 'N',
				'values' => array('N', 'Y')
			),
			'HAS_PHONE' => array(
				'data_type' => 'boolean',
				'default_value' => 'N',
				'values' => array('N', 'Y')
			),
			'HAS_IMOL' => array(
				'data_type' => 'boolean',
				'default_value' => 'N',
				'values' => array('N', 'Y')
			),
			'EMAIL_HOME' => array(
				'data_type' => 'string',
				'expression' => array(
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'COMPANY\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'EMAIL\' '.
						'AND FM.VALUE_TYPE = \'HOME\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			'EMAIL_WORK' => array(
				'data_type' => 'string',
				'expression' => array(
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'COMPANY\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'EMAIL\' '.
						'AND FM.VALUE_TYPE = \'WORK\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			'PHONE_MOBILE' => array(
				'data_type' => 'string',
				'expression' => array(
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'COMPANY\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'PHONE\' '.
						'AND FM.VALUE_TYPE = \'MOBILE\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			'PHONE_WORK' => array(
				'data_type' => 'string',
				'expression' => array(
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'COMPANY\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'PHONE\' '.
						'AND FM.VALUE_TYPE = \'WORK\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			'IMOL' => array(
				'data_type' => 'string',
				'expression' => array(
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'COMPANY\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'IM\' '.
						'AND FM.VALUE LIKE \'imol|%%\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),

			'EMAIL' => array(
				'data_type' => 'string',
				'expression' => array(
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'COMPANY\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'EMAIL\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			'PHONE' => array(
				'data_type' => 'string',
				'expression' => array(
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'COMPANY\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'PHONE\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			'SEARCH_CONTENT' => array(
				'data_type' => 'string'
			)
		);
	}
}

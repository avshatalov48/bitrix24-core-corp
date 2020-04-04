<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Main\DB;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\IntegerField;

Loc::loadMessages(__FILE__);

class QuoteTable extends Entity\DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_quote';
	}

	/**
	 * @return string
	 */
	public static function getUfId()
	{
		return 'CRM_QUOTE';
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		global $DB;

		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'TITLE' => array(
				'data_type' => 'string'
			),
			'OPPORTUNITY' => array(
				'data_type' => 'integer'
			),
			'CURRENCY_ID' => array(
				'data_type' => 'float'
			),
			'OPPORTUNITY_ACCOUNT' => array(
				'data_type' => 'float'
			),
			'ACCOUNT_CURRENCY_ID' => array(
				'data_type' => 'string'
			),
			'EXCH_RATE' => array(
				'data_type' => 'float'
			),
			'QUOTE_NUMBER' => array(
				'data_type' => 'string'
			),
			'STATUS_ID' => array(
				'data_type' => 'string'
			),
			'CLOSED' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y')
			),
			'COMMENTS' => array(
				'data_type' => 'string'
			),
			'BEGINDATE' => array(
				'data_type' => 'datetime'
			),
			'BEGINDATE_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'BEGINDATE'
				)
			),
			'CLOSEDATE' => array(
				'data_type' => 'datetime'
			),
			'CLOSEDATE_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'CLOSEDATE'
				)
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime'
			),
			'DATE_CREATE_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'DATE_CREATE'
				)
			),
			'DATE_MODIFY' => array(
				'data_type' => 'datetime'
			),
			'DATE_MODIFY_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'DATE_MODIFY'
				)
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
			'DEAL_ID' => array(
				'data_type' => 'integer'
			),
			'LEAD_ID' => array(
				'data_type' => 'integer'
			),
			'LEAD_BY' => array(
				'data_type' => 'Lead',
				'reference' => array('=this.LEAD_ID' => 'ref.ID')
			),
			'CONTACT_ID' => array(
				'data_type' => 'integer'
			),
			'CONTACT_BY' => array(
				'data_type' => 'Contact',
				'reference' => array('=this.CONTACT_ID' => 'ref.ID')
			),
			'COMPANY_ID' => array(
				'data_type' => 'integer'
			),
			'COMPANY_BY' => array(
				'data_type' => 'Company',
				'reference' => array('=this.COMPANY_ID' => 'ref.ID')
			),
			'HAS_PRODUCTS' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN EXISTS (SELECT ID FROM b_crm_product_row WHERE OWNER_ID = %s AND OWNER_TYPE = \'Q\') THEN 1 ELSE 0 END',
					'ID'
				),
				'values' => array(0, 1)
			),
			'SEARCH_CONTENT' => array(
				'data_type' => 'string'
			),
			'ELEMENTS' => array(
				'data_type' => '\Bitrix\Crm\QuoteElementTable',
				'reference' => array(
					'=this.ID' => 'ref.QUOTE_ID'
				),
				'join_type' => 'INNER',
			),
			new IntegerField('MYCOMPANY_ID'),
			new Entity\StringField('LOCATION_ID'),
		);
	}
}

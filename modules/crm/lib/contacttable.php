<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Main\IO\Path;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Fields\StringField;

Loc::loadMessages(Path::combine(__DIR__, 'contact.php'));

/**
 * Class ContactTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Contact_Query query()
 * @method static EO_Contact_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Contact_Result getById($id)
 * @method static EO_Contact_Result getList(array $parameters = array())
 * @method static EO_Contact_Entity getEntity()
 * @method static \Bitrix\Crm\Contact createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_Contact_Collection createCollection()
 * @method static \Bitrix\Crm\Contact wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_Contact_Collection wakeUpCollection($rows)
 */
class ContactTable extends ORM\Data\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_contact';
	}

	public static function getUFId()
	{
		return 'CRM_CONTACT';
	}

	public static function getObjectClass()
	{
		return Contact::class;
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
			'NAME' => array(
				'data_type' => 'string'
			),
			'LAST_NAME' => array(
				'data_type' => 'string'
			),
			'SECOND_NAME' => array(
				'data_type' => 'string'
			),
			'SHORT_NAME' => array(
				'data_type' => 'string',
				'expression' => array(
					$DB->concat("%s","' '", "UPPER(".$DB->substr("%s", 1, 1).")", "'.'"),
					'LAST_NAME', 'NAME'
				)
			),
			'LOGIN' => array(
				'data_type' => 'string',
				'expression' => array('NULL')
			),
			'POST' => array(
				'data_type' => 'string'
			),
			'ADDRESS' => array(
				'data_type' => 'string'
			),
			'COMMENTS' => array(
				'data_type' => 'string'
			),
			'TYPE_ID' => array(
				'data_type' => 'string'
			),
			'TYPE_BY' => array(
				'data_type' => 'Status',
				'reference' => array(
					'=this.TYPE_ID' => 'ref.STATUS_ID',
					'=ref.ENTITY_ID' => array('?', 'CONTACT_TYPE')
				)
			),
			'SOURCE_ID' => array(
				'data_type' => 'string'
			),
			'SOURCE_BY' => array(
				'data_type' => 'Status',
				'reference' => array(
					'=this.SOURCE_ID' => 'ref.STATUS_ID',
					'=ref.ENTITY_ID' => array('?', 'SOURCE')
				)
			),
			'SOURCE_DESCRIPTION' => array(
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
			'BIRTHDATE' => array(
				'data_type' => 'date'
			),
			'EVENT_RELATION' => array(
				'data_type' => 'EventRelations',
				'reference' => array('=this.ID' => 'ref.ENTITY_ID')
			),
			'LEAD_ID' => array(
				'data_type' => 'integer'
			),
			'COMPANY_ID' => array(
				'data_type' => 'integer'
			),
			'COMPANY' => array(
				'data_type' => '\Bitrix\Crm\Company',
				'reference' => array('=this.COMPANY_ID' => 'ref.ID')
			),
			'SEARCH_CONTENT' => array(
				'data_type' => 'string'
			),
			'HONORIFIC' => array(
				'data_type' => 'string'
			),
			'FULL_NAME' => array(
				'data_type' => 'string'
			),
			'FACE_ID' => array(
				'data_type' => 'integer'
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
						'WHERE FM.ENTITY_ID = \'CONTACT\' '.
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
						'WHERE FM.ENTITY_ID = \'CONTACT\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'EMAIL\' '.
						'AND FM.VALUE_TYPE = \'WORK\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			'EMAIL_MAILING' => array(
				'data_type' => 'string',
				'expression' => array(
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'CONTACT\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'EMAIL\' '.
						'AND FM.VALUE_TYPE = \'MAILING\' '.
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
						'WHERE FM.ENTITY_ID = \'CONTACT\' '.
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
						'WHERE FM.ENTITY_ID = \'CONTACT\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'PHONE\' '.
						'AND FM.VALUE_TYPE = \'WORK\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			'PHONE_MAILING' => array(
				'data_type' => 'string',
				'expression' => array(
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'CONTACT\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'PHONE\' '.
						'AND FM.VALUE_TYPE = \'MAILING\' '.
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
						'WHERE FM.ENTITY_ID = \'CONTACT\' '.
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
						'WHERE FM.ENTITY_ID = \'CONTACT\' '.
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
						'WHERE FM.ENTITY_ID = \'CONTACT\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'PHONE\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			new StringField('PHOTO'),
		);
	}
}

<?php

namespace Bitrix\Crm\WebForm\Internals;

use Bitrix\Main\ORM;

/**
 * Class FormFieldMappingTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_FormFieldMapping_Query query()
 * @method static EO_FormFieldMapping_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_FormFieldMapping_Result getById($id)
 * @method static EO_FormFieldMapping_Result getList(array $parameters = [])
 * @method static EO_FormFieldMapping_Entity getEntity()
 * @method static \Bitrix\Crm\WebForm\Internals\EO_FormFieldMapping createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\WebForm\Internals\EO_FormFieldMapping_Collection createCollection()
 * @method static \Bitrix\Crm\WebForm\Internals\EO_FormFieldMapping wakeUpObject($row)
 * @method static \Bitrix\Crm\WebForm\Internals\EO_FormFieldMapping_Collection wakeUpCollection($rows)
 */
class FormFieldMappingTable extends ORM\Data\DataManager
{

	public static function getTableName()
	{
		return 'b_crm_webform_ads_field_mapping';
	}

	public static function getMap()
	{
		return [
			'FORM_LINK_ID' => [
				'data_type' => 'integer',
				'primary' => true,
			],
			'CRM_FIELD_KEY' => [
				'data_type' => 'string',
				'required' => true
			],
			'ADS_FIELD_KEY' => [
				'data_type' => 'string',
				'required' => true,
			],
			'ITEMS' => [
				'data_type' => 'text',
				'serialized' => true
			],
			'FORM_LINK' => [
				'data_type' => '\Bitrix\Crm\Ads\Internals\AdsFormLinkTable',
				'reference' => ['=this.FORM_LINK_ID' => 'ref.ID']
			],
		];
	}
}

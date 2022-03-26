<?php

namespace Bitrix\Crm\WebForm\Internals;

use Bitrix\Main\ORM;

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

<?php

namespace Bitrix\Crm\Integration\BiConnector;

class EntityRelationMapping
{

	public static function getMapping(): array
	{
		return [
			'TABLE_NAME' => 'b_crm_entity_relation',
			'TABLE_ALIAS' => 'ER',
			'FIELDS' => [
				'SRC_ENTITY_TYPE_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'ER.SRC_ENTITY_TYPE_ID',
					'FIELD_TYPE' => 'int',
				],
				'SRC_ENTITY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'ER.SRC_ENTITY_ID',
					'FIELD_TYPE' => 'int',
				],
				'SRC_ENTITY_DATASET_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'IF(DTS.ID, CONCAT(\'crm_dynamic_items_\', DTS.ENTITY_TYPE_ID), ' . static::getNameByField('ER.SRC_ENTITY_TYPE_ID'). ')',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'DTS',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_dynamic_type DTS ON ER.SRC_ENTITY_TYPE_ID = DTS.ENTITY_TYPE_ID',
				],
				'DST_ENTITY_TYPE_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'ER.DST_ENTITY_TYPE_ID',
					'FIELD_TYPE' => 'int',
				],
				'DST_ENTITY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'ER.DST_ENTITY_ID',
					'FIELD_TYPE' => 'int',
				],
				'DST_ENTITY_DATASET_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'IF(DTD.ID, CONCAT(\'crm_dynamic_items_\', DTD.ENTITY_TYPE_ID), ' . static::getNameByField('ER.DST_ENTITY_TYPE_ID'). ')',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'DTD',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_dynamic_type DTD ON ER.DST_ENTITY_TYPE_ID = DTD.ENTITY_TYPE_ID',
				],
			],
		];
	}

	private static function getNameByField(string $field): string
	{
		return <<<SQL
CASE 
	WHEN $field = 1 THEN 'crm_lead'
	WHEN $field = 2 THEN 'crm_deal'
	WHEN $field = 3 THEN 'crm_contact'
	WHEN $field = 4 THEN 'crm_company'
	ELSE 'null'
END
SQL;
	}
}

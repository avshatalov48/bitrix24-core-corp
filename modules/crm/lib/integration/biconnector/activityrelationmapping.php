<?php

namespace Bitrix\Crm\Integration\BiConnector;

class ActivityRelationMapping
{
	public static function getMapping(): array
	{
		return [
			'TABLE_NAME' => 'b_crm_act_bind',
			'TABLE_ALIAS' => 'AB',
			'FIELDS' => [
				//  `ID` int unsigned NOT NULL AUTO_INCREMENT,
				'ACTIVITY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'AB.ACTIVITY_ID',
					'FIELD_TYPE' => 'int',
				],
				'OWNER_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'AB.OWNER_ID',
					'FIELD_TYPE' => 'int',
				],
				'OWNER_TYPE_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'AB.OWNER_TYPE_ID',
					'FIELD_TYPE' => 'int',
				],
				'CREATED_AT' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'CA.CREATED',
					'FIELD_TYPE' => 'datetime',
					'TABLE_ALIAS' => 'CA',
					'JOIN' => 'INNER JOIN b_crm_act CA ON AB.ACTIVITY_ID = CA.ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_act CA ON AB.ACTIVITY_ID = CA.ID',
				],
			],
		];
	}
}
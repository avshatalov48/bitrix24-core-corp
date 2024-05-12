<?php

namespace Bitrix\Crm\Integration\BiConnector;

class DynamicTypeMapping
{
	public static function getMapping(): array
	{
		return [
			'TABLE_NAME' => 'b_crm_dynamic_type',
			'TABLE_ALIAS' => 'DT',
			'FIELDS' => [
				//  `ID` int unsigned NOT NULL AUTO_INCREMENT,
				//  `ENTITY_TYPE_ID` int NOT NULL,
				'ENTITY_TYPE_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'DT.ENTITY_TYPE_ID',
					'FIELD_TYPE' => 'int',
				],
				//  `CODE` varchar(255) DEFAULT NULL,
				//  `NAME` varchar(255) NOT NULL,
				//  `TITLE` varchar(255) NOT NULL,
				'TITLE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'DT.TITLE',
					'FIELD_TYPE' => 'string',
				],
				//  `TABLE_NAME` varchar(64) NOT NULL,
				'DATASET_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'RIGHT(DT.TABLE_NAME, LENGTH(DT.TABLE_NAME) - 2)',
					'FIELD_TYPE' => 'string',
				],
				//  `CREATED_BY` int unsigned NOT NULL,
				//  `IS_CATEGORIES_ENABLED` char(1) NOT NULL DEFAULT 'N',
				//  `IS_STAGES_ENABLED` char(1) NOT NULL DEFAULT 'N',
				//  `IS_BEGIN_CLOSE_DATES_ENABLED` char(1) NOT NULL DEFAULT 'N',
				//  `IS_CLIENT_ENABLED` char(1) NOT NULL DEFAULT 'N',
				//  `IS_LINK_WITH_PRODUCTS_ENABLED` char(1) NOT NULL DEFAULT 'N',
				//  `IS_CRM_TRACKING_ENABLED` char(1) NOT NULL DEFAULT 'N',
				//  `IS_MYCOMPANY_ENABLED` char(1) NOT NULL DEFAULT 'N',
				//  `IS_DOCUMENTS_ENABLED` char(1) NOT NULL DEFAULT 'N',
				//  `IS_SOURCE_ENABLED` char(1) NOT NULL DEFAULT 'N',
				//  `IS_USE_IN_USERFIELD_ENABLED` char(1) NOT NULL DEFAULT 'N',
				//  `IS_OBSERVERS_ENABLED` char(1) NOT NULL DEFAULT 'N',
				//  `IS_RECYCLEBIN_ENABLED` char(1) NOT NULL DEFAULT 'N',
				//  `IS_AUTOMATION_ENABLED` char(1) NOT NULL DEFAULT 'N',
				//  `IS_BIZ_PROC_ENABLED` char(1) NOT NULL DEFAULT 'N',
				//  `IS_SET_OPEN_PERMISSIONS` char(1) NOT NULL DEFAULT 'Y',
				//  `IS_PAYMENTS_ENABLED` char(1) NOT NULL DEFAULT 'N',
				//  `IS_COUNTERS_ENABLED` char(1) NOT NULL DEFAULT 'N',
				//  `CREATED_TIME` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				//  `UPDATED_TIME` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				//  `UPDATED_BY` int unsigned NOT NULL,
			],
		];
	}
}
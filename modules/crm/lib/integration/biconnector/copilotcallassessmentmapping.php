<?php

namespace Bitrix\Crm\Integration\BiConnector;

class CopilotCallAssessmentMapping
{
	public static function getMapping(): array
	{
		return [
			'TABLE_NAME' => 'b_crm_copilot_call_assessment',
			'TABLE_ALIAS' => 'CCA',
			'FIELDS' => [
				// ID int NOT NULL,
				'ID' => [
					'IS_PRIMARY' => 'Y',
					'FIELD_NAME' => 'CCA.ID',
					'FIELD_TYPE' => 'int',
				],
				// TITLE varchar(255) NOT NULL,
				'TITLE' => [
					'FIELD_NAME' => 'CCA.TITLE',
					'FIELD_TYPE' => 'string',
				],
				// PROMPT text NOT NULL,
				'PROMPT' => [
					'FIELD_NAME' => 'CCA.PROMPT',
					'FIELD_TYPE' => 'string',
				],
				// GIST text,
				// CALL_TYPE tinyint(1) NOT NULL,
				// AUTO_CHECK_TYPE tinyint(1) NOT NULL,
				// IS_ENABLED char(1)  NOT NULL DEFAULT 'Y',
				'IS_ENABLED' => [
					'FIELD_NAME' => 'CCA.IS_ENABLED',
					'FIELD_TYPE' => 'string',
				],
				// IS_DEFAULT char(1)  NOT NULL DEFAULT 'N',
				// JOB_ID int NOT NULL DEFAULT '0',
				// STATUS varchar(100) NOT NULL DEFAULT 'SUCCESS',
				'STATUS' => [
					'FIELD_NAME' => 'CCA.STATUS',
					'FIELD_TYPE' => 'string',
				],
				// CODE varchar(20)  DEFAULT NULL,
				// CREATED_AT datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				'CREATED_AT' => [
					'FIELD_NAME' => 'CCA.CREATED_AT',
					'FIELD_TYPE' => 'datetime',
				],
				// UPDATED_AT datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				'UPDATED_AT' => [
					'FIELD_NAME' => 'CCA.UPDATED_AT',
					'FIELD_TYPE' => 'datetime',
				],
				// CREATED_BY_ID int NOT NULL DEFAULT '0',
				'CREATED_BY_ID' => [
					'FIELD_NAME' => 'CCA.CREATED_BY_ID',
					'FIELD_TYPE' => 'int',
				],
				// UPDATED_BY_ID int NOT NULL DEFAULT '0',
				'UPDATED_BY_ID' => [
					'FIELD_NAME' => 'CCA.UPDATED_BY_ID',
					'FIELD_TYPE' => 'int',
				],
				// LOW_BORDER tinyint NOT NULL DEFAULT '0',
				'LOW_BORDER' => [
					'FIELD_NAME' => 'CCA.LOW_BORDER',
					'FIELD_TYPE' => 'int',
				],
				// HIGH_BORDER tinyint NOT NULL DEFAULT '100',
				'HIGH_BORDER' => [
					'FIELD_NAME' => 'CCA.HIGH_BORDER',
					'FIELD_TYPE' => 'int',
				],
				// AVAILABILITY_PERIOD_ID tinyint NOT NULL DEFAULT '0',
				// AVAILABILITY_TYPE varchar(20) NOT NULL DEFAULT 'always_active'
			],
		];
	}
}

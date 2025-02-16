<?php

namespace Bitrix\Crm\Integration\BiConnector;

class AiQualityAssessmentMapping
{
	public static function getMapping(): array
	{
		return [
			'TABLE_NAME' => 'b_crm_ai_quality_assessment',
			'TABLE_ALIAS' => 'AQA',
			'FIELDS' => [
				//    ID bigint NOT NULL AUTO_INCREMENT,
				'ID' => [
					'IS_PRIMARY' => 'Y',
					'FIELD_NAME' => 'AQA.ID',
					'FIELD_TYPE' => 'int',
				],
				//    CREATED_AT datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				'CREATED_AT' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'AQA.CREATED_AT',
					'FIELD_TYPE' => 'datetime',
				],
				// 	  UPDATED_AT datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				//    ACTIVITY_TYPE tinyint(1) NOT NULL,
				//    ACTIVITY_ID bigint NOT NULL,
				'ACTIVITY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'AQA.ACTIVITY_ID',
					'FIELD_TYPE' => 'int',
				],
				//    ASSESSMENT_SETTING_ID bigint NOT NULL,
				//    JOB_ID bigint NOT NULL,
				//    PROMPT text NOT NULL,
				//    ASSESSMENT int NOT NULL DEFAULT 0,
				'ASSESSMENT' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'AQA.ASSESSMENT',
					'FIELD_TYPE' => 'int',
				],
				//    ASSESSMENT_AVG int(3) NOT NULL DEFAULT 0,
				//    USE_IN_RATING char(1) NOT NULL DEFAULT 'Y',
				'USE_IN_RATING' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'AQA.USE_IN_RATING',
					'FIELD_TYPE' => 'string',
				],
				//    RATED_USER_ID bigint NOT NULL DEFAULT 0,
				'RATED_USER_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'AQA.RATED_USER_ID',
					'FIELD_TYPE' => 'int',
				],
				//    MANAGER_USER_ID bigint NOT NULL DEFAULT 0,
				//    RATED_USER_CHAT_ID bigint NOT NULL DEFAULT 0,
				//    MANAGER_USER_CHAT_ID bigint NOT NULL DEFAULT 0,
			],
		];
	}
}
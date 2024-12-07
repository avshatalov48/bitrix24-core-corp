<?php

namespace Bitrix\Crm\Integration\BiConnector;

use Bitrix\Crm\AutomatedSolution\Entity\AutomatedSolutionTable;

class AutomatedSolutionMapping
{
	public static function getMapping(string $languageId): array
	{
		$solutions = AutomatedSolutionTable::query()->setSelect(['ID', 'TITLE'])->fetchCollection();

		$result = [];
		foreach ($solutions as $solution) {
			$result['crm_automated_solution_' . $solution->getId()] = [
				'TABLE_NAME' => 'b_crm_dynamic_type',
				'TABLE_DESCRIPTION' => Localization::getMessage('CRM_AUTOMATED_SOLUTION_TABLE', $languageId, ['#TITLE#' => $solution->getTitle()]) ?? $solution->getTitle(),
				'TABLE_ALIAS' => 'DT',
				'FILTER' => [
					'=CUSTOM_SECTION_ID' => $solution->getId(),
				],
				'FIELDS' => [
					//  `ID` int unsigned NOT NULL AUTO_INCREMENT,
					//  `ENTITY_TYPE_ID` int NOT NULL,
					'ENTITY_TYPE_ID' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'DT.ENTITY_TYPE_ID',
						'FIELD_TYPE' => 'int',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_SMART_PROC_FIELD_ENTITY_TYPE_ID', $languageId),
					],
					//  `CODE` varchar(255) DEFAULT NULL,
					//  `NAME` varchar(255) NOT NULL,
					//  `TITLE` varchar(255) NOT NULL,
					'TITLE' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'DT.TITLE',
						'FIELD_TYPE' => 'string',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_SMART_PROC_FIELD_TITLE', $languageId),
					],
					//  `TABLE_NAME` varchar(64) NOT NULL,
					'DATASET_NAME' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'RIGHT(DT.TABLE_NAME, LENGTH(DT.TABLE_NAME) - 2)',
						'FIELD_TYPE' => 'string',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_SMART_PROC_FIELD_DATASET_NAME', $languageId),
					],
					'AUTOMATED_SOLUTION_DATASET_NAME' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'if(DT.CUSTOM_SECTION_ID is null, "crm_smart_proc", concat_ws(\'\', "crm_automated_solution_", DT.CUSTOM_SECTION_ID))',
						'FIELD_TYPE' => 'string',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_SMART_PROC_FIELD_AUTOMATED_SOLUTION_DATASET_NAME', $languageId),
					],
					'CUSTOM_SECTION_ID' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'DT.CUSTOM_SECTION_ID',
						'FIELD_TYPE' => 'int',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_SMART_PROC_FIELD_CUSTOM_SECTION_ID', $languageId),
					],
					'CUSTOM_SECTION_TITLE' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'if(DT.CUSTOM_SECTION_ID is null, "CRM", CS.TITLE)',
						'FIELD_TYPE' => 'string',
						'TABLE_ALIAS' => 'CS',
						'LEFT_JOIN' => 'LEFT JOIN b_crm_automated_solution CS ON CS.ID = DT.CUSTOM_SECTION_ID',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_SMART_PROC_FIELD_CUSTOM_SECTION_TITLE', $languageId),
					],
				],
			];
		}

		return $result;
	}
}
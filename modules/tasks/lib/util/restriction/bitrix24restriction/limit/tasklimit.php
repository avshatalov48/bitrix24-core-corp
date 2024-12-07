<?php
namespace Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit;

use Bitrix\Tasks\Integration\Bitrix24\FeatureDictionary;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit;

/**
 * Class TaskLimit
 *
 * @package Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit
 */
class TaskLimit extends Limit
{
	protected static $variableName = FeatureDictionary::VARIABLE_TASKS_LIMIT;

	protected static array $listRelatedFeatures = [
		FeatureDictionary::TASK_OBSERVERS_PARTICIPANTS,
		FeatureDictionary::TASK_DELEGATING,
		FeatureDictionary::TASK_RATE,
		FeatureDictionary::TASK_CONTROL,
		FeatureDictionary::TASK_SUPERVISOR_VIEW,
		FeatureDictionary::TASK_CUSTOM_FIELDS,
		FeatureDictionary::TASK_RECURRING_TASKS,
		FeatureDictionary::TASK_TEMPLATES_SUBTASKS,
		FeatureDictionary::TASK_TEMPLATE_ACCESS_PERMISSIONS,
		FeatureDictionary::TASK_ROBOTS,
		FeatureDictionary::TASK_EFFICIENCY,
		FeatureDictionary::TASK_RECYCLE_BIN_RESTORE,
	];
}

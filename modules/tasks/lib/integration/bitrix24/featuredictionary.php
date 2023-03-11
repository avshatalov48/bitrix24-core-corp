<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Integration\Bitrix24;


class FeatureDictionary
{
	public const TASKS_RECURRENT = 'tasks_recurrent';
	public const TASKS_TEMPLATES_ACCESS = 'tasks_templates_access';
	public const TASKS_AUTOMATION = 'tasks_automation';
	public const TASKS_PERMISSIONS = 'tasks_permissions';
	public const TASKS_GANTT = 'gant';
	public const TASKS_USER_FIELD = 'task_user_field';
	public const TASKS_NETWORK = 'bitrix24_network';
	public const TASKS_RECYCLEBIN = 'recyclebin';

	public const VARIABLE_RECURRING_LIMIT = 'tasks_recurrent_disabling_limit';
	public const VARIABLE_TASKS_LIMIT = 'tasks_functional_disabling_limit';
	public const VARIABLE_KPI_LIMIT = 'tasks_kpi_disabling_limit';
	public const VARIABLE_TEMPLATE_SUBTASKS = 'tasks_template_subtasks';
	public const VARIABLE_SCRUM_LIMIT = 'tasks_scrum_functional_disabling_limit';
	public const VARIABLE_USER_FIELD_LIMIT = 'tasks_user_field_disabling_limit';
	public const VARIABLE_SEARCH_LIMIT = 'tasks_entity_search_limit';
}

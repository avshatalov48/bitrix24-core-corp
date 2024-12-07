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
	public const TASK_CRM_INTEGRATION = 'tasks_crm_integration';
	public const TASK_MAIL_USER_INTEGRATION = 'tasks_mail_user_integration';
	public const TASK_CALENDAR_INTEGRATION = 'tasks_calendar_integration';
	public const TASK_OBSERVERS_PARTICIPANTS = 'tasks_observers_participants';
	public const TASK_RELATED_SUBTASK_DEADLINES = 'tasks_related_subtask_deadlines';
	public const TASK_STATUS_SUMMARY = 'tasks_status_summary';
	public const TASK_RATE = 'tasks_rate';
	public const TASK_CONTROL = 'tasks_control';
	public const TASK_EFFICIENCY = 'tasks_efficiency';
	public const TASK_TIME_TRACKING = 'tasks_time_tracking';
	public const TASK_RECYCLE_BIN_RESTORE = 'tasks_recycle_bin_restore';
	public const TASK_ACCESS_PERMISSIONS = 'tasks_access_permissions';
	public const TASK_SUPERVISOR_VIEW = 'tasks_supervisor_view';
	public const TASK_DELEGATING = 'tasks_delegating';
	public const TASK_SKIP_WEEKENDS = 'tasks_skip_weekends';
	public const TASK_TIME_ELAPSED = 'tasks_time_elapsed';
	public const TASK_RECURRING_TASKS = 'tasks_recurring_tasks';
	public const TASK_TEMPLATES_SUBTASKS = 'tasks_templates_subtasks';
	public const TASK_TEMPLATE_ACCESS_PERMISSIONS = 'tasks_template_access_permissions';
	public const TASK_ROBOTS = 'tasks_robots';
	public const TASK_CUSTOM_FIELDS = 'tasks_custom_fields';
	public const TASK_REPORTS = 'tasks_reports';

	public const VARIABLE_RECURRING_LIMIT = 'tasks_recurrent_disabling_limit';
	public const VARIABLE_TASKS_LIMIT = 'tasks_functional_disabling_limit';
	public const VARIABLE_KPI_LIMIT = 'tasks_kpi_disabling_limit';
	public const VARIABLE_TEMPLATE_SUBTASKS = 'tasks_template_subtasks';
	public const VARIABLE_SCRUM_LIMIT = 'tasks_scrum_functional_disabling_limit';
	public const VARIABLE_SCRUM_CREATE_LIMIT = 'tasks_scrum_number_limit';
	public const VARIABLE_USER_FIELD_LIMIT = 'tasks_user_field_disabling_limit';
	public const VARIABLE_SEARCH_LIMIT = 'tasks_entity_search_limit';
}

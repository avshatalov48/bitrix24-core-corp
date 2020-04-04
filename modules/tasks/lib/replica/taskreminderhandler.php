<?php
namespace Bitrix\Tasks\Replica;

class TaskReminderHandler extends \Bitrix\Replica\Client\BaseHandler
{
	protected $tableName = "b_tasks_reminder";
	protected $moduleId = "tasks";
	protected $className = "\\Bitrix\\Tasks\\Internals\\Task\\ReminderTable";

	protected $primary = array(
		"ID" => "auto_increment",
	);
	protected $predicates = array(
		"TASK_ID" => "b_tasks.ID",
		"USER_ID" => "b_user.ID",
	);
	protected $translation = array(
		"ID" => "b_tasks_reminder.ID",
		"TASK_ID" => "b_tasks.ID",
		"USER_ID" => "b_user.ID",
	);
}

<?php
namespace Bitrix\Tasks\Replica;

class TaskViewedHandler extends \Bitrix\Replica\Client\BaseHandler
{
	protected $tableName = "b_tasks_viewed";
	protected $moduleId = "tasks";
	protected $className = "\\Bitrix\\Tasks\\Internals\\Task\\ViewedTable";

	protected $primary = array(
		"TASK_ID" => "b_tasks.ID",
		"USER_ID" => "b_user.ID",
	);
	protected $predicates = array(
		"TASK_ID" => "b_tasks.ID",
		"USER_ID" => "b_user.ID",
	);
	protected $translation = array(
		"TASK_ID" => "b_tasks.ID",
		"USER_ID" => "b_user.ID",
	);
}

<?php
namespace Bitrix\Tasks\Replica;

class TaskMemberHandler extends \Bitrix\Replica\Client\BaseHandler
{
	protected $tableName = "b_tasks_member";
	protected $moduleId = "tasks";
	protected $className = "\\Bitrix\\Tasks\\Internals\\Task\\MemberTable";

	protected $primary = array(
		"TASK_ID" => "b_tasks.ID",
		"USER_ID" => "b_user.ID",
		"TYPE" => "string",
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

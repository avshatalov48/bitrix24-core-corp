<?php
namespace Bitrix\Tasks\Replica;

class TaskLogHandler extends \Bitrix\Replica\Client\BaseHandler
{
	protected $tableName = "b_tasks_log";
	protected $moduleId = "tasks";
	protected $className = "\\Bitrix\\Tasks\\Internals\\Task\\LogTable";

	protected $primary = array(
		"ID" => "auto_increment",
	);
	protected $predicates = array(
		"TASK_ID" => "b_tasks.ID",
		"USER_ID" => "b_user.ID",
	);

	public function __construct()
	{
		$this->translation = array(
			"ID" => "b_tasks_log.ID",
			"TASK_ID" => "b_tasks.ID",
			"USER_ID" => "b_user.ID",
			"FROM_VALUE" => array($this, "fromValueTranslation"),
			"TO_VALUE" => array($this, "toValueTranslation"),
		);
	}

	/**
	 * Called before log write. You may return false and not log write will take place.
	 *
	 * @param array $record Database record.
	 *
	 * @return boolean
	 */
	public function beforeLogInsert(array $record)
	{
		return $record["FIELD"] === "GROUP_ID"? false: true;
	}

	/**
	 * Returns relation depending on record values.
	 *
	 * @param array $record Database record.
	 * @return string|false
	 */
	public static function fromValueTranslation($record)
	{
		if ($record["FROM_VALUE"] > 0)
		{
			if ($record["FIELD"] === "COMMENT")
			{
				return "b_forum_message.ID";
			}
			elseif ($record["FIELD"] === "RESPONSIBLE_ID")
			{
				return "b_user.ID";
			}
		}
		return false;
	}

	/**
	 * Returns relation depending on record values.
	 *
	 * @param array $record Database record.
	 * @return string|false
	 */
	public static function toValueTranslation($record)
	{
		if ($record["TO_VALUE"] > 0)
		{
			if ($record["FIELD"] === "COMMENT")
			{
				return "b_forum_message.ID";
			}
			elseif ($record["FIELD"] === "RESPONSIBLE_ID")
			{
				return "b_user.ID";
			}
		}
		return false;
	}

	/**
	 * Called before record transformed for log writing.
	 *
	 * @param array &$record Database record.
	 *
	 * @return void
	 */
	public function beforeLogFormat(array &$record)
	{
		if ($record["FIELD"] === "AUDITORS" || $record["FIELD"] === "ACCOMPLICES")
		{
			if ($record["FROM_VALUE"])
				$record["FROM_VALUE"] = $this->usersFromLocalToRemote($record["FROM_VALUE"]);
			if ($record["TO_VALUE"])
				$record["TO_VALUE"] = $this->usersFromLocalToRemote($record["TO_VALUE"]);
		}
	}

	/**
	 * Method will be invoked before new database record inserted.
	 *
	 * @param array &$newRecord All fields of inserted record.
	 *
	 * @return void
	 */
	public function beforeInsertTrigger(array &$newRecord)
	{
		if ($newRecord["FIELD"] === "AUDITORS" || $newRecord["FIELD"] === "ACCOMPLICES")
		{
			if ($newRecord["FROM_VALUE"])
				$newRecord["FROM_VALUE"] = $this->usersFromRemoteToLocal($newRecord["FROM_VALUE"]);
			if ($newRecord["TO_VALUE"])
				$newRecord["TO_VALUE"] = $this->usersFromRemoteToLocal($newRecord["TO_VALUE"]);
		}
	}

	/**
	 * Converts list of user identifiers to the string of guids separated with commas.
	 *
	 * @param string $userList Comma separated list of user identifiers.
	 *
	 * @return string
	 */
	public function usersFromLocalToRemote($userList)
	{
		$mapper = \Bitrix\Replica\Mapper::getInstance();
		$result = array();
		foreach (explode(",", $userList) as $userId)
		{
			$logGuid = $mapper->getLogGuid("b_user.ID", $userId);
			if ($logGuid)
				$result[] = $logGuid;
		}
		return implode(",", $result);
	}

	/**
	 * Converts list of GUIDs to the string of user identifiers separated with comma.
	 *
	 * @param string $userList Comma separated list of users GUIDs.
	 *
	 * @return string
	 */
	public function usersFromRemoteToLocal($userList)
	{
		$mapper = \Bitrix\Replica\Mapper::getInstance();
		$result = array();
		foreach (explode(",", $userList) as $logGuid)
		{
			$userId = $mapper->resolveLogGuid("", "b_user.ID", $logGuid);
			if ($userId)
				$result[] = $userId;
		}
		return implode(",", $result);
	}
}

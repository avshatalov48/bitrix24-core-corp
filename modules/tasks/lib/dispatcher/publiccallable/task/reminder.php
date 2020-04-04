<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 * 
 * @access private
 * 
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME, so be careful.
 * 
 * Todo: add some policy in this entity
 */

namespace Bitrix\Tasks\Dispatcher\PublicCallable\Task;

final class Reminder extends \Bitrix\Tasks\Dispatcher\PublicCallable
{
	/**
	 * Get all reminders for a specified task
	 */
	/*
	public function getListByTask($taskId, array $order = array())
	{
		global $USER;

		$result = array();

		if($taskId = $this->checkTaskId($taskId))
		{
			$res = \CTaskReminders::getList($order, array('TASK_ID' => $taskId));
			while($item = $res->fetch())
			{
				$result[] = $item;
			}
		}

		return $result;
	}
	*/

	/**
	 * Get all reminders for the current user
	 */
	public function getListByCurrentUser()
	{
		global $USER;

		$result = array();

		$res = \CTaskReminders::getList($order, array('USER_ID' => $USER->GetId()));
		while($item = $res->fetch())
		{
			$result['DATA']['REMINDER'][] = $item;
		}

		return $result;
	}

	/**
	 * Add a new reminder
	 */
	public function add(array $data, array $parameters = array())
	{
		global $USER;

		$result = array();

		if($taskId = $this->checkTaskId($data['TASK_ID']))
		{
			// todo: introduce a new policy here: who can add a new reminder for a different user OR even for himself?
			$reminder = new \CTaskReminders();
			$field = array(
				"TASK_ID" => 		$taskId,
				"USER_ID" => 		$USER->GetId(),
				"REMIND_DATE" => 	$data["DATE"],
				"TYPE" => 			$data["TYPE"],
				"TRANSPORT" => 		$data["TRANSPORT"]
			);
			if(!$obReminder->add($field))
			{
				throw new Tasks\ActionFailedException('Reminder add', array(
					'AUX' => array(
						'ERROR' => array(
							'TASK_ID' => $taskId,
						),
					)
				));
			}
		}

		return $result;
	}

	/**
	 * Delete all reminders for the current user
	 */
	public function deleteByCurrentUser()
	{
		global $USER;

		\CTaskReminders::DeleteByUserID($USER->GetId());

		return array();
	}
}
<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 *
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME via AJAX and\or REST, so be careful.
 *
 * Todo: add some policy in this entity
 */

namespace Bitrix\Tasks\Dispatcher\PublicAction\Task;

use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;

final class Reminder extends \Bitrix\Tasks\Dispatcher\RestrictedAction
{

	/**
	 * Get all reminders for the current user
	 */
	public function getListByCurrentUser()
	{
		$result = array();

		$res = \CTaskReminders::getList(false, array('USER_ID' => $this->userId));
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
		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, (int)$data['TASK_ID']))
		{
			$this->addForbiddenError();
			return $result;
		}

		if($taskId = $this->checkTaskId($data['TASK_ID']))
		{
			// todo: introduce a new policy here: who can add a new reminder for a different user OR even for himself?
			$reminder = new \CTaskReminders();
			$field = array(
				"TASK_ID" => 		$taskId,
				"USER_ID" => 		$this->userId,
				"REMIND_DATE" => 	$data["DATE"],
				"TYPE" => 			$data["TYPE"],
				"TRANSPORT" => 		$data["TRANSPORT"]
			);
			if(!$reminder->add($field))
			{
				throw new \Bitrix\Tasks\ActionFailedException('Reminder add', array(
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
		\CTaskReminders::DeleteByUserID($this->userId);

		return array();
	}
}
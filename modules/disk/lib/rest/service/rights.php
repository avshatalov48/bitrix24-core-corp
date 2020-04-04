<?php


namespace Bitrix\Disk\Rest\Service;


use Bitrix\Disk\Driver;

final class Rights extends Base
{
	/**
	 * Returns tasks by module Disk.
	 * @return array
	 */
	protected function getTasks()
	{
		$rightsManager = Driver::getInstance()->getRightsManager();
		$tasks = array(
			$rightsManager->getTaskById($rightsManager->getTaskIdByName($rightsManager::TASK_FULL)),
			$rightsManager->getTaskById($rightsManager->getTaskIdByName($rightsManager::TASK_EDIT)),
			$rightsManager->getTaskById($rightsManager->getTaskIdByName($rightsManager::TASK_READ)),
		);

		foreach($tasks as &$task)
		{
			$task = array_intersect_key($task, array(
				'ID' => true,
				'NAME' => true,
				'TITLE' => true,
			));
		}
		unset($task);

		return $tasks;
	}
}
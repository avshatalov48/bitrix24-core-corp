<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 * 
 * @access private
 * 
 * This class should be used in components, inside agent functions, in rest, ajax and more, bringing unification to all places and processes
 */

namespace Bitrix\Tasks\Manager\Task;

use \Bitrix\Main\Loader;

use \Bitrix\Tasks\Util\Error\Collection;

final class Reminder extends \Bitrix\Tasks\Manager
{
	public static function getIsMultiple()
	{
		return true;
	}

	public static function getListByParentEntity($userId, $taskId, array $parameters = array())
	{
		static::checkCanReadTaskThrowException($userId, $taskId);

		$data = array();
		$res = \CTaskReminders::getList(array("date" => "asc"), array("USER_ID" => $userId, "TASK_ID" => $taskId));
		while($item = $res->Fetch())
		{
			$item[static::ACT_KEY] = array();
			$data[] = $item;
		}

		return array('DATA' => $data, 'CAN' => array());
	}

	public static function manageSet($userId, $taskId, array $items = array(), array $parameters = array('PUBLIC_MODE' => false, 'MODE' => self::MODE_ADD))
	{
		$errors = static::ensureHaveErrorCollection($parameters);
		$result = array(
			'DATA' => array(),
			'CAN' => array(),
			'ERRORS' => $errors
		);

		if(!static::checkSetPassed($items, $parameters['MODE']))
		{
			return $result;
		}

		// todo: temporary commended out, as it makes troubles with rights check on task.update
		//static::checkCanReadTaskThrowException($userId, $taskId);

		// todo: first check all data, then do update...

		$data = array();

		\CTaskReminders::Delete(array(
			"=TASK_ID" => $taskId,
			"=USER_ID" => $userId
		));

		\Bitrix\Tasks\Util\AgentManager::checkAgentIsAlive('sendReminder', 60);

		foreach($items as $item)
		{
			if((string) $item['REMIND_DATE'] == '')
			{
				continue;
			}

			$reminder = new \CTaskReminders(array('USER_ID' => $userId));

			$fields = array(
				'TASK_ID' => 			$taskId,
				'USER_ID' => 			$userId,
				'REMIND_DATE' => 		$item['REMIND_DATE'],
				'TYPE' => 				$item['TYPE'],
				'TRANSPORT' => 			$item['TRANSPORT'],
				'RECEPIENT_TYPE' => 	$item['RECEPIENT_TYPE']
			);

			if(!$reminder->Add($fields))
			{
				$errors->load($reminder->getErrors());
			}
		}

		$result['DATA'] = $data;

		return $result;
	}
}
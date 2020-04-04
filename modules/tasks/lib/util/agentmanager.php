<?
/**
 * Class contains agent functions. Place all new agents here.
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 * 
 * @access private
 */

namespace Bitrix\Tasks\Util;

use Bitrix\Tasks\Item\SystemLog;

final class AgentManager
{
	public static function notificationThrottleRelease()
	{
		\CTaskNotifications::throttleRelease();

		return '\\'.__CLASS__."::notificationThrottleRelease();";
	}

	public static function sendReminder()
	{
		\CTaskReminders::SendAgent();

		return '\\'.__CLASS__."::sendReminder();";
	}

	public static function rotateSystemLog()
	{
		SystemLog::rotate();

		return '\\'.__CLASS__."::rotateSystemLog();";
	}

	public static function createOverdueChats()
	{
		\Bitrix\Tasks\Util\Notification\Task::createOverdueChats();

		return '\\'.__CLASS__."::createOverdueChats();";
	}
	
	public static function checkAgentIsAlive($name, $interval)
	{
		$name = '\\'.__CLASS__.'::'.$name.'();';

		$agent = \CAgent::GetList(array(), array('MODULE_ID' => 'tasks', 'NAME' => $name))->fetch();
		if(!$agent['ID'])
		{
			\CAgent::AddAgent(
				$name,
				'tasks',
				'N', // dont care about how many times agent rises
				$interval
			);
		}
	}

	public static function __callStatic($name, $arguments)
	{
		\Bitrix\Tasks\Util::log('Agent function does not exist: '.get_called_class().'::'.$name.'([args]);');

		return '';
	}
}
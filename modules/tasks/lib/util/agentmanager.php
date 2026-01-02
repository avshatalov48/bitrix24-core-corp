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
use Bitrix\Tasks\V2\Infrastructure\Agent\Reminder;

final class AgentManager
{
	public const LOG_CLEANER_AGENT_NAME = 'rotateSystemLog';
	public const LOG_CLEANER_AGENT_INTERVAL = 60 * 60 * 24 * 3; // 3 days

	public static function notificationThrottleRelease()
	{
		\CTaskNotifications::throttleRelease();

		return '\\'.__CLASS__."::notificationThrottleRelease();";
	}

	/**
	 * @deprecated
	 * @TasksV2
	 * @use Reminder
	 *
	 * Don't remove it because agent with this name may be already registered in the system.
	 */
	public static function sendReminder()
	{
		return Reminder::execute();
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
		if(!isset($agent['ID']))
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
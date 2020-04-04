<?
/**
 * @internal
 * @access private
 *
 * This is a class of temporary functions! It may be removed in ANY MOMENT, so DO NOT rely on it.
 *
 * Add this action as agent in module updater like this. This will start its job 3 minutes after the update installed.
 *
	if (IsModuleInstalled('tasks'))
	{
		\CAgent::AddAgent('\Bitrix\Tasks\Util\DisposableAction::reCreateUserFields();', 'tasks', 'N', 100, '', 'Y', GetTime(time() + 180, "FULL"), 100, false, false);
	}
 *
 */

namespace Bitrix\Tasks\Util;

use Bitrix\Tasks\Integration\Disk;
use Bitrix\Main\Config\Option;

class DisposableAction
{
	public static function reInitializeCounterAgent()
	{
		//\CTaskCountersProcessor::ensureAgentExists();
		return '';
	}

	public static function needConvertTemplateFiles()
	{
		return User::isSuper() && Disk::isInstalled() && Option::get('tasks', 'template.files.converted') != '1';
	}

	public static function reCreateUserFields()
	{
		$adminId = User::getAdminId();

		\Bitrix\Tasks\Util\UserField\Task::getScheme(0, $adminId, LANGUAGE_ID, true);
		\Bitrix\Tasks\Util\UserField\Task\Template::getScheme(0, $adminId, LANGUAGE_ID, true);
	}

	public static function restoreReplicationAgents()
	{
		global $DB;

		// get all tasks that has replication = on
		$tasks = array();
		$res = $DB->query("
			select TT.ID as TT_ID, TT.REPLICATE, TT.REPLICATE_PARAMS as REPLICATE_PARAMS, TT.TPARAM_REPLICATION_COUNT as TT_TPARAM_REPLICATION_COUNT, TT.CREATED_BY as TT_CREATED_BY from b_tasks_template TT
			  	where TT.REPLICATE = 'Y'
		");
		while($item = $res->fetch())
		{
			$tasks[$item['TT_ID']] = $item;
		}

		// get all agents of name "CTasks::RepeatTaskByTemplateId(nnnn"
		$agents = array();
		$res = $DB->query("select NAME from b_agent where MODULE_ID = 'tasks' and ACTIVE = 'Y'");
		while($item = $res->fetch())
		{
			$found = array();

			if(preg_match('#^CTasks::RepeatTaskByTemplateId\((\d+)#', $item['NAME'], $found))
			{
				$templateId = intval($found[1]);
				if($templateId)
				{
					$agents[$templateId] = $item;
				}
			}
		}

		// for each task check what we must do with the corresponding agent
		foreach($tasks as $taskId => $taskData)
		{
			$name = 'CTasks::RepeatTaskByTemplateId('.$taskData['TT_ID'].');';
			$rParams = unserialize($taskData['REPLICATE_PARAMS']);

			$endDate = (string) $rParams['END_DATE'];
			if($endDate != '' && MakeTimeStamp($endDate) < time())
			{
				if(isset($agents[$taskId])) // end date in the past, but agent still exists - remove it
				{
					\CAgent::RemoveAgent($agents[$taskData['TT_ID']]['NAME'], 'tasks');
				}
			}
			else
			{
				if(!array_key_exists($taskData['TT_ID'], $agents))
				{
					$nextTime = \CTasks::getNextTime($rParams, array(
						'ID' => $taskData['TT_ID'],
						'CREATED_BY' => $taskData['TT_CREATED_BY'],
						'TPARAM_REPLICATION_COUNT' => $taskData['TT_TPARAM_REPLICATION_COUNT'],
					));

					if ($nextTime) // task will be repeated, so add agent, if there is no such
					{
						\CAgent::AddAgent(
							$name,
							'tasks',
							'N', 		// is periodic?
							86400, 		// interval (24 hours)
							$nextTime, 	// datecheck
							'Y', 		// is active?
							$nextTime	// next_exec
						);
					}
				}
			}
		}
	}
}
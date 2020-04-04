<?
/**
 * Class implements all further interactions with "socialnetwork" module considering "task item" entity
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 * 
 * @access private
 */

namespace Bitrix\Tasks\Integration\Socialnetwork;

use Bitrix\Main\Loader;

final class Task extends \Bitrix\Tasks\Integration\Socialnetwork
{
	public static function getCommentForumId()
	{
		return \Bitrix\Tasks\Integration\Forum\Task\Comment::getForumId();
	}

	/**
	 * See CSocNetLogFavorites::Add() and CSocNetLogFavorites::Change()
	 */
	public static function onSonetLogFavorites(array $params)
	{
		$params['USER_ID'] = intval($params['USER_ID']);
		$params['LOG_ID'] = intval($params['LOG_ID']);

		if($params['USER_ID'] && $params['LOG_ID'] && static::includeModule())
		{
			$res = \CSocNetLog::GetById($params['LOG_ID']);
			if(
				!empty($res)
				&& $res['EVENT_ID'] == 'tasks'
				&& intval($res['SOURCE_ID']) > 0
			)
			{
				$taskId = intval($res['SOURCE_ID']);
				try
				{
					$task = new \CTaskItem($taskId, $params['USER_ID']); // ensure task exists

					if($params['OPERATION'] == 'ADD')
					{
						$task->addToFavorite(array('TELL_SOCNET' => false));
					}
					else
					{
						$task->deleteFromFavorite(array('TELL_SOCNET' => false));
					}
				}
				catch(\TasksException $e)
				{
					return;
				}
			}
		}
	}

	public static function toggleFavorites(array $params)
	{
		$params['TASK_ID'] = intval($params['TASK_ID']);
		$params['USER_ID'] = intval($params['USER_ID']);

		if($params['TASK_ID'] && $params['USER_ID'] && static::includeModule())
		{
			// get all soc net log records considering this task and user
			$res = \CSocNetLog::GetList(
				array(),
				array('EVENT_ID' => 'tasks', 'SOURCE_ID' => $params['TASK_ID'], 'USER_ID' => $params['USER_ID']),
				false,
				false,
				array('ID', 'USER_ID')
			);
			while($item = $res->fetch())
			{
				// add them to favorite
				if($params['OPERATION'] == 'ADD')
				{
					\CSocNetLogFavorites::Add($item['USER_ID'], $item['ID'], array('TRIGGER_EVENT' => false));
				}
				else
				{
					\CSocNetLogFavorites::Change($item['USER_ID'], $item['ID'], array('TRIGGER_EVENT' => false));
				}
			}
		}
	}

	public static function getSonetLogByTaskId($taskId)
	{
		static $cache = array();

		$result = array();

		$taskId = intval($taskId);
		if(!static::includeModule() || $taskId <= 0)
		{
			return $result;
		}

		if (isset($cache[$taskId]))
		{
			return $cache[$taskId];
		}
		else
		{
			$res = \CSocNetLog::getList(
				array(),
				array('EVENT_ID' => 'tasks', 'SOURCE_ID' => $taskId),
				false,
				false,
				array('ID')
			);
			if ($item = $res->fetch())
			{
				$result = $item;
			}

			if (
				empty($result)
				&& Loader::includeModule('crm')
			)
			{
				$res = \CCrmActivity::getList(
					array(),
					array(
						'TYPE_ID' => \CCrmActivityType::Task,
						'ASSOCIATED_ENTITY_ID' => $taskId,
						'CHECK_PERMISSIONS' => 'N'
					),
					false,
					false,
					array('ID')
				);
				if ($crmActivity = $res->fetch())
				{
					$res = \CSocNetLog::getList(
						array(),
						array('EVENT_ID' => 'crm_activity_add', 'ENTITY_ID' => $crmActivity['ID']),
						false,
						false,
						array('ID')
					);
					if ($item = $res->fetch())
					{
						$result = $item;
					}
				}
			}

			$cache[$taskId] = $result;
			return $result;
		}
	}

	public static function addContextToURL($url, $taskId)
	{
		if(!static::includeModule())
		{
			return $url;
		}

		$log = static::getSonetLogByTaskId($taskId);

		$context = array();
		if(!empty($log))
		{
			$context = array(
				"ENTITY_TYPE" => "LOG_ENTRY",
				"ENTITY_ID" => $log['ID']
			);
		}

		return \Bitrix\Socialnetwork\ComponentHelper::addContextToUrl($url, $context);
	}
}
<?
/**
 * This class contains ui helper for task entity
 *
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Tasks\UI;

use Bitrix\Main\Loader;
use Bitrix\Tasks\Integration;
use Bitrix\Tasks\Util;

final class Task
{
	public static function makeCopyUrl($url, $taskId)
	{
		$taskId = intval($taskId);
		if(!$taskId)
		{
			return $url;
		}

		return Util::replaceUrlParameters($url, array('_COPY' => $taskId));
	}

	public static function makeCreateSubtaskUrl($url, $taskId)
	{
		$taskId = intval($taskId);
		if(!$taskId)
		{
			return $url;
		}

		return Util::replaceUrlParameters($url, array('PARENT_ID' => $taskId));
	}

	public static function makeFireEventUrl($url, $taskId, $eventType, array $eventOptions = [])
	{
		$taskId = (int)$taskId;
		if (!$taskId)
		{
			return $url;
		}

		$urlParams = [
			'EVENT_TYPE' => $eventType,
			'EVENT_TASK_ID' => $taskId,
			'EVENT_OPTIONS[STAY_AT_PAGE]' => $eventOptions['STAY_AT_PAGE'],
			'EVENT_OPTIONS[SCOPE]' => $eventOptions['SCOPE'],
			'EVENT_OPTIONS[FIRST_GRID_TASK_CREATION_TOUR_GUIDE]' => $eventOptions['FIRST_GRID_TASK_CREATION_TOUR_GUIDE'],
		];

		return Util::replaceUrlParameters($url, $urlParams, array_keys($urlParams));
	}

	public static function cleanFireEventUrl($url)
	{
		$urlParams = [
			'EVENT_TYPE',
			'EVENT_TASK_ID',
			'EVENT_OPTIONS[STAY_AT_PAGE]',
			'EVENT_OPTIONS[SCOPE]',
			'EVENT_OPTIONS[FIRST_GRID_TASK_CREATION_TOUR_GUIDE]',
		];

		return Util::replaceUrlParameters($url, [], $urlParams);
	}

	/**
	 * Returns path for url action.
	 * @deprecated
	 * @return string
	 */
	public static function getActionPath(): string
	{
		if (Integration\Extranet::isExtranetSite())
		{
			$urlPrefix = '/extranet/contacts/personal';
		}
		else
		{
			$optionPath = (string)\COption::getOptionString('intranet', 'path_task_user_entry');
			if ($optionPath !== '')
			{
				$optionPath = (string)\COption::getOptionString('tasks', 'paths_task_user_action');
			}

			if ($optionPath !== '')
			{
				return $optionPath;
			}

			// todo: if $siteId is set, use its dir, not SITE_DIR
			$urlPrefix = (defined('SITE_DIR') ? SITE_DIR : '/').'company/personal';
		}

		return "{$urlPrefix}/user/#user_id#/tasks/task/#action#/#task_id#/";
	}

	/**
	 * Returns url for task action.
	 *
	 * @param $path
	 * @param int $taskId
	 * @param string $actionId
	 * @param int $userId
	 * @return string
	 */
	public static function makeActionUrl($path, $taskId = 0, $actionId = 'edit', $userId = 0): string
	{
		if ((string)$path === '')
		{
			return '';
		}

		$actionId = ($actionId === 'edit' ? 'edit' : 'view');
		$userId = (int)$userId;
		if (!$userId)
		{
			$userId = Util\User::getId();
		}

		$map = [
			'action' => $actionId,
			'ACTION' => $actionId,
			'user_id' => $userId,
			'USER_ID' => $userId,
		];
		// special case, leave task placeholder un-replaced
		if ($taskId !== false)
		{
			$map['task_id'] = (int)$taskId;
			$map['TASK_ID'] = (int)$taskId;
		}

		return \CComponentEngine::MakePathFromTemplate($path, $map);
	}

	public static function getUserInfo($userId, $needFullName = true)
	{
		static $users = array();

		if (!array_key_exists($userId, $users))
		{
			$select = array('ID', 'PERSONAL_PHOTO', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'EXTERNAL_AUTH_ID');
			if (Loader::includeModule('crm'))
			{
				$select[] = 'UF_USER_CRM_ENTITY';
			}
			$res = \Bitrix\Main\UserTable::getList(
				array(
					'select' => $select,
					'filter' => array(
						'ID' => $userId
					)
				)
			);
			$user = (array)$res->fetch();

			$site = \Bitrix\Tasks\Util\Site::get(SITE_ID);
			$siteId = $site["SITE_ID"];

			$users[$userId] = array(
				'ID' => $user['ID'],
				'NAME' => htmlspecialcharsbx(Util\User::formatName($user, $siteId)),
				'AVATAR' => \Bitrix\Tasks\UI::getAvatar($user['PERSONAL_PHOTO'], 100, 100),
				'IS_EXTERNAL' => \Bitrix\Tasks\Util\User::isExternalUser($user['ID']),
				'IS_CRM' => array_key_exists('UF_USER_CRM_ENTITY', $user) && !empty($user['UF_USER_CRM_ENTITY'])
			);

		}

		return $users[$userId];
	}
}
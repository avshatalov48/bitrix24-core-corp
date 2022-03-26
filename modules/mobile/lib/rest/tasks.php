<?php
namespace Bitrix\Mobile\Rest;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Internals\Effective;
use Bitrix\Tasks\Internals\Project\Pull\PullDictionary;
use Bitrix\Tasks\Kanban\TimeLineTable;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\KpiLimit;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;

/**
 * Class Tasks
 *
 * @package Bitrix\Mobile\Rest
 */
class Tasks extends \IRestService
{
	/**
	 * @return array|array[]
	 */
	public static function getMethods(): array
	{
		return [
			'mobile.tasks.deadlines.get' => [
				'callback' => [__CLASS__, 'getDeadlines'],
				'options' => ['private' => false],
			],
			'mobile.tasks.group.search' => [
				'callback' => [__CLASS__, 'searchGroups'],
				'options' => ['private' => false],
			],
			'mobile.tasks.group.lastActive.get' => [
				'callback' => [__CLASS__, 'getLastActiveGroups'],
				'options' => ['private' => false],
			],
			'mobile.tasks.group.lastSearched.validate' => [
				'callback' => [__CLASS__, 'validateLastSearchedGroups'],
				'options' => ['private' => false],
			],
			'mobile.tasks.group.getCanCreateTask' => [
				'callback' => [__CLASS__, 'getCanCreateTask'],
				'options' => ['private' => false],
			],
			'mobile.task.link.params.get' => [
				'callback' => [__CLASS__, 'getParamsToCreateLink'],
				'options' => ['private' => false],
			],
			'mobile.tasks.efficiency.get' => [
				'callback' => [__CLASS__, 'getEfficiency'],
				'options' => ['private' => false],
			],
			'mobile.tasks.project.list.startWatch' => [
				'callback' => [__CLASS__, 'startWatchProjectList']
			],
		];
	}

	/**
	 * @return array
	 * @throws Main\ObjectException
	 */
	public static function getDeadlines(): array
	{
		$tomorrow = MakeTimeStamp(TimeLineTable::getDateClient().' 23:59:59') + 86400;
		$deadlines = ['tomorrow' => (new DateTime(TimeLineTable::getClosestWorkHour($tomorrow)))->getTimestamp()];
		$map = [
			'PERIOD2' => 'today',
			'PERIOD3' => 'thisWeek',
			'PERIOD4' => 'nextWeek',
			'PERIOD6' => 'moreThanTwoWeeks',
		];
		foreach (TimeLineTable::getStages() as $key => $val)
		{
			if (array_key_exists($key, $map))
			{
				$deadlines[$map[$key]] = (new DateTime($val['UPDATE']['DEADLINE']))->getTimestamp();
			}
		}

		return $deadlines;
	}

	/**
	 * @return array
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	 */
	public static function getLastActiveGroups(): array
	{
		$logDestination = SocialNetwork::getLogDestination();

		$lastGroups = $logDestination['LAST']['SONETGROUPS'];
		foreach ($lastGroups as $key => $group)
		{
			$lastGroups[$key] = str_replace('SG', '', $group);
		}

		return static::prepareGroups($lastGroups);
	}

	public static function validateLastSearchedGroups(array $params): array
	{
		return static::prepareGroups($params['ids']);
	}

	private static function prepareGroups(array $groupIds): array
	{
		$data = SocialNetwork\Group::getData($groupIds, ['IMAGE_ID']);

		foreach ($groupIds as $key => $id)
		{
			if (array_key_exists($id, $data))
			{
				$group = $data[$id];
				$group['id'] = $group['ID'];
				$group['name'] = $group['NAME'];
				$group['image'] = (is_array($file = \CFile::GetFileArray($group['IMAGE_ID'])) ? $file['SRC'] : '');
				unset($group['ID'], $group['NAME'], $group['IMAGE_ID'], $group['EXPANDED']);
				$groupIds[$key] = $group;
			}
		}
		$groupIds = array_filter(
			$groupIds,
			static function ($group) {
				return is_array($group);
			}
		);

		return array_values($groupIds);
	}

	/**
	 * @param array $params
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function searchGroups(array $params): array
	{
		$groups = [];

		$foundGroups = SocialNetwork\Group::searchGroups(trim($params['searchText']), ['IMAGE_ID']);
		foreach ($foundGroups as $foundGroup)
		{
			$group = [
				'id' => $foundGroup['ID'],
				'name' => $foundGroup['NAME'],
				'image' => (is_array($file = \CFile::GetFileArray($foundGroup['IMAGE_ID'])) ? $file['SRC'] : ''),
			];
			$groups[] = $group;
		}

		return $groups;
	}

	/**
	 * @param array $params
	 *
	 * @return string
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws \Bitrix\Rest\RestException
	 * @throws \CTaskAssertException
	 */
	public static function getParamsToCreateLink(array $params): string
	{
		if (!Loader::includeModule('mobile'))
		{
			throw new \Bitrix\Rest\RestException(
				'Module mobile is not installed', 'SERVER_ERROR', \CRestServer::STATUS_WRONG_REQUEST
			);
		}

		$taskId = (int)$params['taskId'];

		return \CMobileHelper::getParamsToCreateTaskLink($taskId);
	}

	public static function getEfficiency(array $params)
	{
		$modules = ['mobile', 'tasks', 'socialnetwork'];
		foreach ($modules as $name)
		{
			if (!Loader::includeModule($name))
			{
				throw new \Bitrix\Rest\RestException(
					"Module {$name} is not installed",
					'SERVER_ERROR',
					\CRestServer::STATUS_WRONG_REQUEST
				);
			}
		}

		$userId = (int)$params['userId'];
		$groupId = (int)$params['groupId'];

		if (!$userId && !$groupId)
		{
			throw new \Bitrix\Rest\RestException(
				'No data to get efficiency',
				'DATA_ERROR',
				\CRestServer::STATUS_WRONG_REQUEST
			);
		}

		$currentUserId = User::getId();
		if (!$userId)
		{
			$userId = $currentUserId;
		}

		if (KpiLimit::isLimitExceeded())
		{
			return false;
		}

		if (
			$currentUserId !== $userId
			&& !User::isSuper($currentUserId)
			&& !User::isBossRecursively($currentUserId, $userId)
		)
		{
			return false;
		}

		if ($groupId && !SocialNetwork\Group::canReadGroupTasks($userId, $groupId))
		{
			return false;
		}

		return Effective::getAverageEfficiency(null, null, $userId, $groupId);
	}

	public static function startWatchProjectList(array $params): bool
	{
		$modules = ['mobile', 'tasks'];
		foreach ($modules as $name)
		{
			if (!Loader::includeModule($name))
			{
				throw new \Bitrix\Rest\RestException(
					"Module {$name} is not installed",
					'SERVER_ERROR',
					\CRestServer::STATUS_WRONG_REQUEST
				);
			}
		}

		$userId = ((int)$params['userId'] ?: User::getId());

		return \CPullWatch::Add($userId, PullDictionary::PULL_PROJECTS_TAG, true);
	}

	public static function getCanCreateTask(array $params): bool
	{
		$modules = ['mobile', 'tasks', 'socialnetwork'];
		foreach ($modules as $name)
		{
			if (!Loader::includeModule($name))
			{
				throw new \Bitrix\Rest\RestException(
					"Module {$name} is not installed",
					'SERVER_ERROR',
					\CRestServer::STATUS_WRONG_REQUEST
				);
			}
		}

		$userId = (int)$params['userId'];
		$groupId = (int)$params['groupId'];

		if (!$userId && !$groupId)
		{
			throw new \Bitrix\Rest\RestException(
				'No data to get efficiency',
				'DATA_ERROR',
				\CRestServer::STATUS_WRONG_REQUEST
			);
		}

		$userId = ($userId ?: User::getId());

		return SocialNetwork\Group::can($groupId, SocialNetwork\Group::ACTION_CREATE_TASKS, $userId);
	}
}
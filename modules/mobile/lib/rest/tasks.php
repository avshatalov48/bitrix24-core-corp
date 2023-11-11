<?php
namespace Bitrix\Mobile\Rest;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Kanban\TimeLineTable;
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
			'mobile.tasks.group.getCanCreateTask' => [
				'callback' => [__CLASS__, 'getCanCreateTask'],
				'options' => ['private' => false],
			],
			'mobile.task.link.params.get' => [
				'callback' => [__CLASS__, 'getParamsToCreateLink'],
				'options' => ['private' => false],
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
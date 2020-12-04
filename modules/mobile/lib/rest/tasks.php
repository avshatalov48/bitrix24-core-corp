<?php
namespace Bitrix\Mobile\Rest;

use Bitrix\Main;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Kanban\TimeLineTable;
use Bitrix\Tasks\Util\Type\DateTime;

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
			'mobile.tasks.group.last.get' => [
				'callback' => [__CLASS__, 'getLastGroups'],
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
	 * @return array
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	 */
	public static function getLastGroups(): array
	{
		$logDestination = SocialNetwork::getLogDestination();

		$lastGroups = $logDestination['LAST']['SONETGROUPS'];
		foreach ($lastGroups as $key => $group)
		{
			$lastGroups[$key] = str_replace('SG', '', $group);
		}

		$lastGroupsData = SocialNetwork\Group::getData($lastGroups, ['IMAGE_ID']);
		foreach ($lastGroups as $sgId => $id)
		{
			if (array_key_exists($id, $lastGroupsData))
			{
				$group = $lastGroupsData[$id];
				$group['id'] = $group['ID'];
				$group['name'] = $group['NAME'];
				$group['image'] = (is_array($file = \CFile::GetFileArray($group['IMAGE_ID'])) ? $file['SRC'] : '');
				unset($group['ID'], $group['NAME'], $group['IMAGE_ID'], $group['EXPANDED']);
				$lastGroups[$sgId] = $group;
			}
		}

		return array_values($lastGroups);
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
}
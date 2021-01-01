<?php
namespace Bitrix\Timeman\Monitor\History;

use Bitrix\Main\Type\Date;
use Bitrix\Timeman\Monitor\Group\EntityType;
use Bitrix\Timeman\Monitor\Group\Group;
use Bitrix\Timeman\Monitor\Group\UserAccess;

class History
{
	private $dateStart;
	private $dateFinish;
	private $userId;
	private $history;

	public function __construct(int $userId, Date $dateStart, Date $dateFinish)
	{
		$this->userId = $userId;
		$this->dateStart = $dateStart;
		$this->dateFinish = $dateFinish;
		$this->history = UserLog::getForPeriod($this->userId, $this->dateStart, $this->dateFinish);
	}

	public function get(): array
	{
		return $this->history;
	}

	public function applyGroup(Group $group): void
	{
		$groupAccess = $group->getAccesses();
		$userAccess = UserAccess::getAccessForUser($this->userId);
		$maskGroup = $group->getMasks();

		foreach ($this->history as $key => $historyEntry)
		{
			if ($historyEntry['SITE_ID'])
			{
				if($userAccess[EntityType::SITE][$historyEntry['SITE_ID']])
				{
					$this->history[$key]['GROUP_CODE'] = $userAccess[EntityType::SITE][(int)$historyEntry['SITE_ID']]['GROUP_CODE'];
					continue;
				}

				$hostGroup = self::getHostGroupByMasks($historyEntry['SITE_HOST'], $maskGroup);
				if ($hostGroup)
				{
					$this->history[$key]['GROUP_CODE'] = $hostGroup['GROUP_CODE'];
					$this->history[$key]['ADDED_BY_MASK'] = true;
				}
				elseif (array_key_exists((int)$historyEntry['SITE_ID'], $groupAccess[EntityType::SITE]))
				{
					$this->history[$key]['GROUP_CODE'] = $groupAccess[EntityType::SITE][(int)$historyEntry['SITE_ID']];
				}
			}
			elseif ($historyEntry['APP_ID'])
			{
				if($userAccess[EntityType::APP][$historyEntry['APP_ID']])
				{
					$this->history[$key]['GROUP_CODE'] = $userAccess[EntityType::APP][(int)$historyEntry['APP_ID']]['GROUP_CODE'];
					continue;
				}

				if (array_key_exists((int)$historyEntry['APP_ID'], $groupAccess[EntityType::APP]))
				{
					$this->history[$key]['GROUP_CODE'] = $groupAccess[EntityType::APP][(int)$historyEntry['APP_ID']];
				}
			}
		}
	}

	public static function getHostGroupByMasks($host, $masks)
	{
		foreach ($masks as $mask => $groupCode)
		{
			$isHostMatchesMask = (mb_substr($host, mb_strlen($host) - mb_strlen($mask)) === $mask);
			if ($isHostMatchesMask)
			{
				return [
					'MASK' => $mask,
					'GROUP_CODE' => $groupCode
				];
			}
		}

		return false;
	}

	public static function record($history): bool
	{
		UserLog::add($history);
		UserPage::add($history);

		$sites = self::getSitesForAdd($history);
		if ($sites)
		{
			Site::add($sites);
		}

		$apps = self::getAppsForAdd($history);
		if ($apps)
		{
			App::add($apps);
		}

		return true;
	}

	private static function getSitesForAdd($history): array
	{
		$sites = [];
		foreach ($history as $entries)
		{
			foreach ($entries as $entry)
			{
				if (!$entry['siteCode'])
				{
					continue;
				}

				if (!in_array(['code' => $entry['siteCode'], 'host' => $entry['host']], $sites, true))
				{
					$sites[] = [
						'code' => $entry['siteCode'],
						'host' => $entry['host']
					];
				}
			}
		}

		return $sites;
	}

	private static function getAppsForAdd($history): array
	{
		$apps = [];
		foreach ($history as $entries)
		{
			foreach ($entries as $entry)
			{
				if (!$entry['appCode'])
				{
					continue;
				}

				if (!in_array(['code' => $entry['appCode'], 'name' => $entry['appName']], $apps, true))
				{
					$apps[] = [
						'code' => $entry['appCode'],
						'name' => $entry['appName']
					];
				}
			}
		}

		return $apps;
	}
}
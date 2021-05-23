<?php
namespace Bitrix\Intranet\Integration\Timeman;

use Bitrix\Main\Config\Option;
use \Bitrix\Main\Type\Date;
use Bitrix\Main\Loader;
use Bitrix\Timeman\Service\DependencyManager;
use Bitrix\Main\ORM\Query\Query;

class Worktime
{
	const STATUS_OPENED = "O";
	const STATUS_CLOSED = "C";

	private static function addTMDayInfoToOption($fields)
	{
		$data = Option::get("intranet", "ustat_online_timeman", "");
		if (!empty($data))
		{
			$data = unserialize($data, ["allowed_classes" => false]);
		}

		$optionDate = isset($data["date"]) && is_numeric($data["date"]) ? $data["date"] : 0;
		$currentDate = new Date;
		$currentDate = $currentDate->getTimestamp();

		if (!$optionDate || $currentDate > $optionDate)
		{
			$newValue = [
				"date" => $currentDate
			];
			$newValue["tm_days"][$fields["userId"]] = $fields["workDayStatus"];
		}
		else
		{
			$newValue = $data;
			$newValue["tm_days"][$fields["userId"]] = $fields["workDayStatus"];
		}

		Option::set("intranet", "ustat_online_timeman", serialize($newValue));

		$currentTMDayData = self::getTMDayData();
		self::sendPush($currentTMDayData);
	}

	public static function getTMDayDataFromDB()
	{
		if (!Loader::includeModule("timeman"))
		{
			return;
		}

		$openedDayCount = 0;
		$closedDayCount = 0;
		$currentDate = new Date;
		$currentDate = $currentDate->getTimestamp();
		$newValue = [
			"date" => $currentDate,
			"tm_days" => []
		];

		$worktimeRepository = DependencyManager::getInstance()->getWorktimeRepository();

		$currentDate = new Date;
		$filter = Query::filter()->where('RECORDED_START_TIMESTAMP', '>=', $currentDate->getTimestamp());

		$records = $worktimeRepository->findAll(['*'], $filter);

		foreach ($records as $record)
		{
			$recordManager = DependencyManager::getInstance()->buildWorktimeRecordManager($record, $record->obtainSchedule(), $record->obtainShift());
			if ($record->getCurrentStatus() === 'OPENED' && !$recordManager->isRecordExpired())
			{
				$openedDayCount++;
				$newValue["tm_days"][$record->getUserId()] = "O";
			}
			elseif ($record->getCurrentStatus() === 'CLOSED')
			{
				$closedDayCount++;
				if (!array_key_exists($record->getUserId(), $newValue["tm_days"]))
				{
					$newValue["tm_days"][$record->getUserId()] = "C";
				}
			}
		}

		Option::set("intranet", "ustat_online_timeman", serialize($newValue));

		$res = [
			"OPENED" => $openedDayCount,
			"CLOSED" => $closedDayCount
		];

		return $res;
	}

	public static function getTMDayData(): array
	{
		$res = [
			"OPENED" => 0,
			"CLOSED" => 0
		];

		$data = Option::get("intranet", "ustat_online_timeman", "");
		if (!empty($data))
		{
			$data = unserialize($data, ["allowed_classes" => false]);

			$optionDate = isset($data["date"]) && is_numeric($data["date"]) ? $data["date"] : 0;
			$currentDate = new Date;
			$currentDate = $currentDate->getTimestamp();

			if ($optionDate && $currentDate <= $optionDate)
			{
				foreach ($data["tm_days"] as $userId => $status)
				{
					if ($status === self::STATUS_OPENED)
					{
						$res["OPENED"]++;
					}
					elseif ($status === self::STATUS_CLOSED)
					{
						$res["CLOSED"]++;
					}
				}
			}
		}
		/*else
		{
			$res = self::getTMDayDataFromDB();
		}*/

		return $res;
	}

	public static function getTMUserData($status = ""): array
	{
		$users = [];

		$status = in_array($status, [self::STATUS_OPENED, self::STATUS_CLOSED]) ? $status : self::STATUS_OPENED;

		$data = Option::get("intranet", "ustat_online_timeman", "");
		if (!empty($data))
		{
			$data = unserialize($data, ["allowed_classes" => false]);

			$optionDate = isset($data["date"]) && is_numeric($data["date"]) ? $data["date"] : 0;
			$currentDate = new Date;
			$currentDate = $currentDate->getTimestamp();

			if ($optionDate && $currentDate <= $optionDate)
			{
				foreach ($data["tm_days"] as $userId => $userStatus)
				{
					if ($userStatus === $status)
					{
						$users[] = $userId;
					}
				}
			}
		}

		return $users;
	}

	public static function OnAfterTMDayStart(array $params)
	{
		$fields = [
			"userId" => $params["USER_ID"],
			"workDayStatus" => self::STATUS_OPENED
		];

		self::addTMDayInfoToOption($fields);
	}

	public static function OnAfterTMDayEnd(array $params)
	{
		if ($params["ACTIVE"] === "N")
		{
			return;
		}

		$fields = [
			"userId" => $params["USER_ID"],
			"workDayStatus" => self::STATUS_CLOSED
		];

		self::addTMDayInfoToOption($fields);
	}

	public static function OnAfterTMDayContinue(array $params)
	{
		self::OnAfterTMDayStart($params);
	}

	public static function sendPush($params)
	{
		if (!Loader::includeModule('pull'))
		{
			return false;
		}

		/*$res = \Bitrix\Pull\Event::add(\Bitrix\Pull\Event::SHARED_CHANNEL, [
			'module_id' => 'intranet',
			'command' => 'timemanDayInfo',
			'params' => $params
		]);*/

		$res = \CPullStack::AddShared(Array(
			'module_id' => 'intranet',
			'command' => 'timemanDayInfo',
			'params' => $params
		));
	}

	public static function OnGetDependentModule(/*\Bitrix\Main\Event $event*/)
	{
		return [
			'MODULE_ID' => "intranet",
			'USE' => ["PUBLIC_SECTION"]
		];
	}

	public static function registerEventHandler()
	{
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->registerEventHandler('timeman', 'OnAfterTMDayStart', 'intranet', self::class, 'OnAfterTMDayStart');
		$eventManager->registerEventHandler('timeman', 'OnAfterTMDayEnd', 'intranet', self::class, 'OnAfterTMDayEnd');
		$eventManager->registerEventHandler('timeman', 'OnAfterTMDayContinue', 'intranet', self::class, 'OnAfterTMDayContinue');
		$eventManager->registerEventHandler("pull", "OnGetDependentModule", "intranet", self::class, "OnGetDependentModule" );
	}
}




<?php
namespace Bitrix\Timeman\Monitor;

use Bitrix\Intranet\Util;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Bitrix\Pull\Event;

final class Config
{
	public const MODULE_ID = 'timeman';
	public const MONITOR_ENABLE_OPTION = 'monitor_enable';
	public const MONITOR_SKIP_OPTION = 'monitor_enable_skip';

	public const TYPE_NONE = 'NONE';
	public const TYPE_ALL = 'ALL';
	public const TYPE_FOR_USER = 'USER';

	private static $bounceTimeout = 30000; //30 sec
	private static $sendTimeout = 1800000; //30 min
	private static $resendTimeout = 600000; //10 min

	public static function get(): array
	{
		return [
			'enabled' => self::getEnabled(),
			'state' => self::getState(),
			'bounceTimeout' => self::$bounceTimeout,
			'sendTimeout' => self::$sendTimeout,
			'resendTimeout' => self::$resendTimeout,
		];
	}

	public static function json()
	{
		return Json::encode(self::get());
	}

	public static function getEnabled(): string
	{
		return (self::isMonitorEnabledForCurrentUser() ? State::ENABLED : State::DISABLED);
	}

	public static function getState(): string
	{
		return (self::isWorkingDayOpened() ? State::START : State::STOP);
	}

	private static function isWorkingDayOpened(): bool
	{
		return (\CTimeManUser::instance()->State() === 'OPENED');
	}

	public static function getMonitorEnableType(): ?string
	{
		$enableType = self::getMonitorEnableOption();
		if ($enableType == '1')
		{
			return self::TYPE_ALL;
		}

		if ($enableType == '0')
		{
			return self::TYPE_NONE;
		}

		return self::TYPE_FOR_USER;
	}

	public static function getMonitorUsers()
	{
		$enableType = self::getMonitorEnableType();
		if ($enableType !== self::TYPE_FOR_USER)
		{
			return $enableType;
		}

		$monitorUsers = self::getMonitorEnableOption();
		return Json::decode($monitorUsers);
	}

	public static function getMonitorSkipUsers()
	{
		return Json::decode(self::getMonitorSkipOption());
	}

	public static function isMonitorEnabledForCurrentUser(): bool
	{
		$userId = $GLOBALS['USER'] ? (int)$GLOBALS['USER']->getId() : 0;
		if(!$userId)
		{
			return false;
		}

		return self::isMonitorEnabledForUser($userId);
	}

	public static function isMonitorEnabledForUser(int $userId): bool
	{
		$monitorUsers = self::getMonitorEnableOption();
		if ($monitorUsers == '1')
		{
			$skipReport = self::getMonitorSkipOption();
			if ($skipReport == '0')
			{
				$result = true;
			}
			else
			{
				$skipReport = Json::decode($skipReport);
				$result = !$skipReport || !in_array($userId, $skipReport, true);
			}
		}
		else if ($monitorUsers == '0')
		{
			$result = false;
		}
		else
		{
			$monitorUsers = Json::decode($monitorUsers);
			$result = $monitorUsers && in_array($userId, $monitorUsers, true);
		}

		return $result;
	}

	public static function enableForUsers($userIds)
	{
		if (!is_array($userIds))
		{
			$userIds = [$userIds];
		}

		$monitorUsers = self::getMonitorUsers();
		if ($monitorUsers === self::TYPE_ALL)
		{
			self::sendChangeMonitorEnabledEvent($userIds,State::ENABLED);
			return;
		}

		if ($monitorUsers === self::TYPE_NONE)
		{
			self::setMonitorEnableOption($userIds);
			self::sendChangeMonitorEnabledEvent($userIds,State::ENABLED);
			return;
		}

		$monitorUsers = array_unique(array_merge($monitorUsers, $userIds));

		self::setMonitorEnableOption($monitorUsers);
		self::sendChangeMonitorEnabledEvent($userIds,State::ENABLED);
	}

	public static function disableForUsers($userIds)
	{
		if (!is_array($userIds))
		{
			$userIds = [$userIds];
		}

		$monitorUsers = self::getMonitorUsers();
		if ($monitorUsers === self::TYPE_NONE)
		{
			self::sendChangeMonitorEnabledEvent($userIds,State::DISABLED);
			return;
		}

		if ($monitorUsers === self::TYPE_ALL)
		{
			self::setMonitorSkipOption($userIds);
			self::sendChangeMonitorEnabledEvent($userIds,State::DISABLED);
			return;
		}

		$monitorUsers = array_diff($monitorUsers, $userIds);

		self::setMonitorEnableOption($monitorUsers);
		self::sendChangeMonitorEnabledEvent($userIds,State::DISABLED);
	}

	public static function enableForAll(): void
	{
		self::setMonitorEnableOption(1);
		self::sendChangeMonitorEnabledEventForAll(State::ENABLED);
	}

	public static function enableForDepartment(int $departmentId): void
	{
		$userIds = self::getUsersByDepartmentId($departmentId);
		if (!$userIds)
		{
			return;
		}

		self::enableForUsers($userIds);
		self::sendChangeMonitorEnabledEvent($userIds,State::ENABLED);
	}

	public static function disableForDepartment(int $departmentId): void
	{
		$userIds = self::getUsersByDepartmentId($departmentId);
		if (!$userIds)
		{
			return;
		}

		self::disableForUsers($userIds);
		self::sendChangeMonitorEnabledEvent($userIds,State::DISABLED);
	}

	private static function getUsersByDepartmentId(int $departmentId)
	{
		if (!Loader::includeModule('intranet'))
		{
			return false;
		}

		$result = Util::getDepartmentEmployees(array(
			'DEPARTMENTS' => $departmentId,
			'RECURSIVE' => 'N',
			'ACTIVE' => 'Y',
			'SELECT' => ['ID']
		));

		$userIds = [];
		while ($row = $result->Fetch())
		{
			$userIds[] = (int)$row['ID'];
		}

		return $userIds;
	}

	public static function disableForAll(): void
	{
		self::setMonitorEnableOption(0);
		self::sendChangeMonitorEnabledEventForAll(State::DISABLED);
	}

	public static function skipUsers($userIds): void
	{
		if (!is_array($userIds))
		{
			$userIds = [$userIds];
		}

		$monitorSkipUsers = self::getMonitorSkipOption();
		if ($monitorSkipUsers == '0')
		{
			self::setMonitorSkipOption($userIds);
			self::sendChangeMonitorEnabledEvent($userIds,State::DISABLED);
			return;
		}

		$monitorSkipUsers = Json::decode($monitorSkipUsers);
		$monitorSkipUsers = array_unique(array_merge($monitorSkipUsers, $userIds));

		self::setMonitorSkipOption($monitorSkipUsers);
	}

	public static function unskipUsers($userIds): void
	{
		if (!is_array($userIds))
		{
			$userIds = [$userIds];
		}

		$monitorSkipUsers = self::getMonitorSkipOption();
		if ($monitorSkipUsers == '0')
		{
			return;
		}

		$monitorSkipUsers = Json::decode($monitorSkipUsers);
		$monitorSkipUsers = array_diff($monitorSkipUsers, $userIds);

		self::setMonitorSkipOption($monitorSkipUsers);

		if (self::getMonitorEnableType() !== self::TYPE_NONE)
		{
			self::sendChangeMonitorEnabledEvent($userIds,State::ENABLED);
		}
	}

	private static function sendChangeMonitorEnabledEvent($recipient, $enabled): void
	{
		if (!in_array($enabled, [State::ENABLED, State::DISABLED], true))
		{
			throw new ArgumentException('Invalid enabled status ' . $enabled);
		}

		if (Loader::includeModule('pull'))
		{
			Event::add($recipient, [
				'module_id' => self::MODULE_ID,
				'command' => 'changeMonitorEnabled',
				'params' => [
					'enabled' => $enabled,
				],
			]);
		}
	}

	private static function sendChangeMonitorEnabledEventForAll($enabled): void
	{
		if (!in_array($enabled, [State::ENABLED, State::DISABLED], true))
		{
			throw new ArgumentException('Invalid enabled status ' . $enabled);
		}

		if (Loader::includeModule('pull'))
		{
			\CPullStack::AddShared([
				'module_id' => self::MODULE_ID,
				'command' => 'changeMonitorEnabled',
				'params' => [
					'enabled' => $enabled
				],
			]);
		}
	}

	private static function setMonitorEnableOption($value): void
	{
		Option::set(self::MODULE_ID, self::MONITOR_ENABLE_OPTION, Json::encode($value));
	}

	private static function getMonitorEnableOption(): string
	{
		return Option::get(self::MODULE_ID, self::MONITOR_ENABLE_OPTION, '0');
	}

	public static function setMonitorSkipOption($value): void
	{
		Option::set(self::MODULE_ID, self::MONITOR_SKIP_OPTION, Json::encode($value));
	}

	public static function getMonitorSkipOption(): string
	{
		return Option::get(self::MODULE_ID, self::MONITOR_SKIP_OPTION, '0');
	}
}
<?php
namespace Bitrix\Timeman\Monitor;

use Bitrix\Intranet\Util;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Bitrix\Pull\Event;
use Bitrix\Timeman\Absence;
use Bitrix\Timeman\Monitor\Constant\State;

final class Config
{
	public const MODULE_ID = 'timeman';
	public const MONITOR_ENABLE_OPTION = 'monitor_enable';
	public const MONITOR_SKIP_OPTION = 'monitor_enable_skip';
	public const MONITOR_DEBUG_ENABLE_OPTION = 'monitor_debug_enable';

	public const TYPE_NONE = 'NONE';
	public const TYPE_ALL = 'ALL';
	public const TYPE_FOR_USER = 'USER';

	private static $resendTimeout = 60000; //1 min
	public static $otherTime = 1800000; //30 min
	public static $shortAbsenceTime = 1800000; //30 min

	public static function isAvailable(): bool
	{
		$timemanPwt = Configuration::getValue('timeman_pwt');

		return (isset($timemanPwt['enabled']) && $timemanPwt['enabled']) ? true : false;
	}

	public static function get(): array
	{
		$culture = Context::getCurrent()->getCulture();

		return [
			'enabled' => self::getEnabled(),
			'debugEnabled' => self::isDebugEnabledForCurrentUser(),
			'resendTimeout' => self::$resendTimeout,
			'otherTime' => self::$otherTime,
			'shortAbsenceTime' => self::$shortAbsenceTime,
			'dateFormat' => [
				'short' => $culture->getShortDateFormat(),
				'long' => $culture->getLongDateFormat(),
			],
			//TODO: Remove hardcode when timeline can work with am/pm
			'timeFormat' => [
				'short' => 'H:i', //$culture->getShortTimeFormat(),
				'long' => 'H:i:s', //$culture->getLongTimeFormat(),
			],
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

	public static function isDebugEnabledForCurrentUser(): bool
	{
		$userId = self::getCurrentUserId();
		if(!$userId)
		{
			return false;
		}

		return self::isMonitorDebugEnabledForUser($userId);
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

	public static function getMonitorDebugEnableType(): ?string
	{
		$enableType = self::getMonitorDebugEnableOption();
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

	public static function getMonitorDebugUsers()
	{
		$enableType = self::getMonitorDebugEnableType();
		if ($enableType !== self::TYPE_FOR_USER)
		{
			return $enableType;
		}

		$debugUsers = self::getMonitorDebugEnableOption();
		return Json::decode($debugUsers);
	}

	public static function getMonitorSkipUsers()
	{
		return Json::decode(self::getMonitorSkipOption());
	}

	protected static function getCurrentUserId() : int
	{
		return $GLOBALS['USER'] ? (int)$GLOBALS['USER']->getId() : 0;
	}

	public static function isMonitorEnabledForCurrentUser(): bool
	{
		$userId = self::getCurrentUserId();
		if(!$userId)
		{
			return false;
		}

		return self::isMonitorEnabledForUser($userId);
	}

	public static function isMonitorEnabledForUser(int $userId): bool
	{
		if (!self::isAvailable())
		{
			return false;
		}

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
		if (!self::isAvailable())
		{
			return;
		}

		Absence::disableForUsers($userIds);

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

	public static function enableForCurrentUser(): bool
	{
		if (!self::isAvailable())
		{
			return false;
		}

		$userId = self::getCurrentUserId();
		if(!$userId)
		{
			return false;
		}

		self::enableForUsers($userId);

		return true;
	}

	public static function disableForUsers($userIds)
	{
		if (!self::isAvailable())
		{
			return;
		}

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
		if (!self::isAvailable())
		{
			return;
		}

		Absence::disableForAll();

		self::setMonitorEnableOption(1);
		self::sendChangeMonitorEnabledEventForAll(State::ENABLED);
	}

	public static function enableForDepartment(int $departmentId): void
	{
		if (!self::isAvailable())
		{
			return;
		}

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
		if (!self::isAvailable())
		{
			return;
		}

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
		if (!self::isAvailable())
		{
			return;
		}

		self::setMonitorEnableOption(0);
		self::sendChangeMonitorEnabledEventForAll(State::DISABLED);
	}

	public static function skipUsers($userIds): void
	{
		if (!self::isAvailable())
		{
			return;
		}

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
		if (!self::isAvailable())
		{
			return;
		}

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

	public static function isMonitorDebugEnabledForCurrentUser(): bool
	{
		$userId = self::getCurrentUserId();
		if(!$userId)
		{
			return false;
		}

		return self::isMonitorDebugEnabledForUser($userId);
	}

	public static function isMonitorDebugEnabledForUser(int $userId): bool
	{
		if (!self::isAvailable())
		{
			return false;
		}

		$debugUsers = self::getMonitorDebugEnableOption();
		if ($debugUsers == '1')
		{
			$result = true;
		}
		else if ($debugUsers == '0')
		{
			$result = false;
		}
		else
		{
			$debugUsers = Json::decode($debugUsers);
			$result = $debugUsers && in_array($userId, $debugUsers, true);
		}

		return $result;
	}

	public static function enableDebugForCurrentUser(): bool
	{
		if (!self::isAvailable())
		{
			return false;
		}

		$userId = self::getCurrentUserId();
		if(!$userId)
		{
			return false;
		}

		self::enableDebugForUsers($userId);

		return true;
	}

	public static function enableDebugForUsers($userIds): void
	{
		if (!self::isAvailable())
		{
			return;
		}

		if (!is_array($userIds))
		{
			$userIds = [$userIds];
		}

		$debugUsers = self::getMonitorDebugUsers();
		if ($debugUsers === self::TYPE_ALL)
		{
			self::sendChangeMonitorDebugEnabledEvent($userIds,true);
			return;
		}

		if ($debugUsers === self::TYPE_NONE)
		{
			self::setMonitorDebugEnableOption($userIds);
			self::sendChangeMonitorDebugEnabledEvent($userIds,true);
			return;
		}

		$debugUsers = array_unique(array_merge($debugUsers, $userIds));

		self::setMonitorDebugEnableOption($debugUsers);
		self::sendChangeMonitorDebugEnabledEvent($userIds,true);
	}

	public static function disableDebugForUsers($userIds)
	{
		if (!self::isAvailable())
		{
			return;
		}

		if (!is_array($userIds))
		{
			$userIds = [$userIds];
		}

		$debugUsers = self::getMonitorDebugUsers();
		if (
			$debugUsers === self::TYPE_NONE
			|| $debugUsers === self::TYPE_ALL
		)
		{
			self::sendChangeMonitorDebugEnabledEvent($userIds,false);
			return;
		}

		$debugUsers = array_diff($debugUsers, $userIds);

		self::setMonitorDebugEnableOption($debugUsers);
		self::sendChangeMonitorDebugEnabledEvent($userIds,false);
	}

	public static function enableDebugForAll(): void
	{
		if (!self::isAvailable())
		{
			return;
		}

		self::setMonitorDebugEnableOption(1);
		self::sendChangeMonitorDebugEnabledEventForAll(true);
	}

	public static function disableDebugForAll(): void
	{
		if (!self::isAvailable())
		{
			return;
		}

		self::setMonitorDebugEnableOption(0);
		self::sendChangeMonitorDebugEnabledEventForAll(false);
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

	private static function sendChangeMonitorDebugEnabledEvent($recipient, bool $enabled): void
	{
		if (Loader::includeModule('pull'))
		{
			Event::add($recipient, [
				'module_id' => self::MODULE_ID,
				'command' => 'changeMonitorDebugEnabled',
				'params' => [
					'enabled' => $enabled,
				],
			]);
		}
	}

	private static function sendChangeMonitorDebugEnabledEventForAll(bool $enabled): void
	{
		if (Loader::includeModule('pull'))
		{
			\CPullStack::AddShared([
				'module_id' => self::MODULE_ID,
				'command' => 'changeMonitorDebugEnabled',
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

	private static function setMonitorDebugEnableOption($value): void
	{
		Option::set(self::MODULE_ID, self::MONITOR_DEBUG_ENABLE_OPTION, Json::encode($value));
	}

	private static function getMonitorDebugEnableOption(): string
	{
		return Option::get(self::MODULE_ID, self::MONITOR_DEBUG_ENABLE_OPTION, '0');
	}
}
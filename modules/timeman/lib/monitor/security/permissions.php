<?php

namespace Bitrix\Timeman\Monitor\Security;

use Bitrix\Main\Config\Configuration;
use Bitrix\Timeman\Monitor\Utils\User;

class Permissions
{
	protected static $instances = [];

	protected $userId;
	protected $availableUserIds;

	public static function createForCurrentUser(): Permissions
	{
		return self::createForUserId(User::getCurrentUserId());
	}

	public static function createForUserId($userId): Permissions
	{
		if(isset(self::$instances[$userId]))
		{
			return self::$instances[$userId];
		}

		$instance = new self;
		$instance->setUserId($userId);
		$instance->availableUserIds = $instance->loadAvailableUserIds();

		self::$instances[$userId] = $instance;

		return $instance;
	}

	protected function loadAvailableUserIds(): array
	{
		$availableUserIds = array_merge([$this->userId], User::getSubordinateEmployees($this->userId));

		$teams = Configuration::getValue('timeman_pwt')['teams'];
		if (is_array($teams))
		{
			$availableUserIdsByTeams = [];
			foreach ($teams as $team)
			{
				$isUserHeadOfTeam = (
					isset($team['head_id'])
					&& ($team['head_id'] === $this->userId)
				);

				$isTeamWithUsers = (
					isset($team['users'])
					&& is_array($team['users'])
				);

				if ($isUserHeadOfTeam && $isTeamWithUsers)
				{
					foreach ($team['users'] as $userId)
					{
						$availableUserIdsByTeams[] = $userId;
					}
				}
			}

			$availableUserIds = array_merge($availableUserIds, $availableUserIdsByTeams);
		}

		return array_unique($availableUserIds);
	}

	public function isUserAvailable(int $userId): bool
	{
		return in_array($userId, $this->getAvailableUserIds(), true);
	}

	public function getAvailableUserIds(): array
	{
		return $this->availableUserIds;
	}

	public function getUserId()
	{
		return $this->userId;
	}

	protected function setUserId($userId): void
	{
		$this->userId = $userId;
	}
}

<?php

namespace Bitrix\Intranet\Service;

use Bitrix\Intranet\Contract;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\User\Access\UserActionDictionary;
use Bitrix\Intranet\User\ActionRule\ActionRule;
use Bitrix\Intranet\User\ActionRule\ActionRuleFactory;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;

class UserService
{
	private Contract\Repository\UserRepository $intranetUserRepository;
	private Cache $cache;
	private const BASE_CACHE_DIR = 'intranet/user/';
	private const CACHE_TTL = 86400;

	public function __construct(?Contract\Repository\UserRepository $userRepository = null)
	{
		$this->intranetUserRepository = $userRepository ?? ServiceContainer::getInstance()->userRepository();
		$this->cache = Application::getInstance()->getCache();
	}

	public function getAdminUserIds(): array
	{
		if ($this->cache->initCache(self::CACHE_TTL, 'admin_id_list', self::BASE_CACHE_DIR))
		{
			$ids = $this->cache->getVars();
		}
		else
		{
			$ids = $this->intranetUserRepository
				->findUsersByUserGroup(1)
				->getIds()
			;

			if ($this->cache->startDataCache())
			{
				$this->cache->endDataCache($ids);
			}
		}

		return $ids;
	}

	public function getIntegratorUserIds(): array
	{
		if ($this->cache->initCache(self::CACHE_TTL, 'integrator_id_list', self::BASE_CACHE_DIR))
		{
			$ids = $this->cache->getVars();
		}
		else
		{
			$ids = [];

			if (Loader::includeModule('bitrix24'))
			{
				$ids = $this->intranetUserRepository
					->findUsersByUserGroup(\CBitrix24::getIntegratorGroupId())
					->getIds()
				;
			}

			if ($this->cache->startDataCache())
			{
				$this->cache->endDataCache($ids);
			}
		}

		return $ids;
	}

	/**
	 * return timestamp value
	 *
	 * @param int $userId
	 * @return int|null
	 */
	public function getLastAuthFromWebTimestamp(int $userId): ?int
	{
		$time = (int)\CUserOptions::GetOption('intranet', 'lastWebAuthorizeTime', 0, $userId);
		if ($time <= 0)
		{
			return null;
		}

		return $time;
	}

	public function setLastAuthFromWebTimestamp(int $userId): void
	{
		\CUserOptions::SetOption('intranet', 'lastWebAuthorizeTime', time(), false, $userId);
	}

	public function logAuthTimeForNonMobile(int $userId): void
	{
		$request = \Bitrix\Main\Context::getCurrent()->getRequest();
		$clientType = \Bitrix\Intranet\Enum\UserAgentType::fromRequest($request);
		if (
			$clientType === \Bitrix\Intranet\Enum\UserAgentType::BROWSER
			|| $clientType === \Bitrix\Intranet\Enum\UserAgentType::DESKTOP
		)
		{
			$this->setLastAuthFromWebTimestamp($userId);
		}
	}

	public function getFormattedInvitationNameByIds(array $userIds): array
	{
		$names = [];

		$this->intranetUserRepository->findUsersByIds($userIds)->forEach(
			function (User $user) use (&$names) {
				$names[$user->getId()] = $user->getAuthPhoneNumber() ?? $user->getEmail() ?? $user->getLogin();
			});

		return $names;
	}

	public function getFirstTimeAuthFromMobileAppTimestamp(int $userId): ?int
	{
		$time = (int)\CUserOptions::GetOption('intranet', 'mobileAuthorizeTime', 0, $userId);
		if ($time <= 0)
		{
			return null;
		}

		return $time;
	}

	public function setFirstTimeAuthFromMobileAppTimestamp(int $userId): void
	{
		\CUserOptions::SetOption('intranet', 'mobileAuthorizeTime', time(), false, $userId);
	}

	public function logFirstTimeAuthForMobile(int $userId): void
	{
		$request = \Bitrix\Main\Context::getCurrent()->getRequest();
		$clientType = \Bitrix\Intranet\Enum\UserAgentType::fromRequest($request);
		if (
			$clientType === \Bitrix\Intranet\Enum\UserAgentType::MOBILE_APP
			&& $this->getFirstTimeAuthFromMobileAppTimestamp($userId) === null
		)
		{
			$this->setFirstTimeAuthFromMobileAppTimestamp($userId);
		}
	}

	public function getDetailUrl(int $userId): string
	{
		return str_replace(
			'#USER_ID#',
			$userId,
			Option::get('intranet', 'path_user', '/company/personal/user/#USER_ID#/', SITE_ID),
		);
	}

	public function getUtcOffset(int $userId): int
	{
		$serverOffset = (int)date('Z');
		$userToServerOffset = \CTimeZone::GetOffset($userId);

		return ($userToServerOffset + $serverOffset);
	}

	public function clearCache(): void
	{
		$this->cache->cleanDir(self::BASE_CACHE_DIR);
	}

	public function isActionAvailableForUser(User $user, UserActionDictionary $action): bool
	{
		return $this->getActionRule($action)->canExecute($user);
	}

	/**
	 * @param User $user
	 * @return array<UserActionDictionary>
	 */
	public function getAvailableActions(User $user): array
	{
		$availableActions = [];

		foreach (UserActionDictionary::cases() as $action)
		{
			if ($this->isActionAvailableForUser($user, $action))
			{
				$availableActions[] = $action;
			}
		}

		return $availableActions;
	}

	private function getActionRule(UserActionDictionary $action): ActionRule
	{
		static $actionRuleSet = [];
		$actionRuleSet[$action->value] ??= ActionRuleFactory::getActionRule($action);

		return $actionRuleSet[$action->value];
	}
}

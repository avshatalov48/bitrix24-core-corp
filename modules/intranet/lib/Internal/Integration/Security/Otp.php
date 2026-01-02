<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Integration\Security;

use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Entity\UserOtp;
use Bitrix\Security\Mfa;
use Bitrix\Main\Loader;
use Bitrix\Security\Mfa\Otp as SecurityOtp;

class Otp
{
	private bool $isEnabled;
	/** @var array<UserOtp> */
	private array $userOtpData = [];
	private const BASE_CACHE_DIR = '/otp/user_id/';
	private const CACHE_ID_PREFIX = 'user_otp_v2_';

	public function __construct()
	{
		$this->isEnabled = Loader::includeModule('security') && Mfa\Otp::isOtpEnabled();
	}

	public function isAvailable(): bool
	{
		return $this->isEnabled;
	}

	public function isActiveByUserId(int $userId): bool
	{
		return $this->isAvailable()
			&& \CSecurityUser::IsUserOtpActive($userId);
	}

	public function isMandatory(): bool
	{
		return $this->isEnabled && SecurityOtp::isMandatoryUsing();
	}

	public function isRequiredForUser(User $user): bool
	{
		if (!$this->isEnabled)
		{
			return false;
		}

		$targetRights = SecurityOtp::getMandatoryRights();
		$currentUserRights = \CAccess::getUserCodesArray($user->getId());
		$existedRights = array_intersect($targetRights, $currentUserRights);

		return !empty($existedRights);
	}

	public function isEnabledForUser(User $user): bool
	{
		if (!$this->isEnabled)
		{
			return false;
		}

		$userOtpData = $this->getUserOtp($user);

		return $userOtpData->isActive && !$userOtpData->dateDeactivate;
	}

	public function getUserOtp(User $user): ?UserOtp
	{
		if (!$this->isEnabled)
		{
			return null;
		}

		if (isset($this->userOtpData[$user->getId()]))
		{
			return $this->userOtpData[$user->getId()];
		}

		if (defined('BX_COMP_MANAGED_CACHE'))
		{
			$ttl = 2592000; // 30 days
		}
		else
		{
			$ttl = 600;
		}

		$cacheId = self::CACHE_ID_PREFIX . $user->getId();
		$cacheDir = self::BASE_CACHE_DIR . substr(md5((string)$user->getId()), -2) . '/' . $user->getId() . '/';
		$cache = \Bitrix\Main\Application::getInstance()->getCache();

		if ($cache->InitCache($ttl, $cacheId, $cacheDir))
		{
			try
			{
				$userOtpData = $cache->GetVars();
				$userOtp = UserOtp::initByArray($userOtpData);
				$this->userOtpData[$user->getId()] = $userOtp;

				return $userOtp;
			}
			catch (\Exception){}
		}

		$otp = SecurityOtp::getByUser($user->getId());
		$userOtp = new UserOtp(
			userId: $otp->getUserId(),
			isActive: $otp->isActivated(),
			dateDeactivate: $otp->getDeactivateUntil(),
		);

		if ($cache->StartDataCache())
		{
			$cache->EndDataCache($userOtp->toArray());
		}

		$this->userOtpData[$user->getId()] = $userOtp;

		return $userOtp;
	}
}

<?php

namespace Bitrix\Sign\Service;

class OnboardingService
{
	private const OPTION_BANNER_VISIBLE = 'Y';
	private const OPTION_BANNER_HIDDEN = 'N';
	private const OPTION_BANNER_UNSEEN = null;

	/** @var array<int, string|null> */
	private static array $bannerVisibilityCache = [];

	public function setBannerVisible(int $userId): void
	{
		$this->setBannerVisibilityOption($userId, self::OPTION_BANNER_VISIBLE);
	}

	public function setBannerHidden(int $userId): void
	{
		$this->setBannerVisibilityOption($userId, self::OPTION_BANNER_HIDDEN);
	}

	public function isBannerSeenByUser(int $userId): bool
	{
		return $this->getBannerVisibilityOption($userId) !== self::OPTION_BANNER_UNSEEN;
	}

	public function isBannerVisible(int $userId): bool
	{
		return $this->getBannerVisibilityOption($userId) === self::OPTION_BANNER_VISIBLE;
	}

	private function getBannerVisibilityOption(int $userId): ?string
	{
		if (array_key_exists($userId, self::$bannerVisibilityCache))
		{
			return self::$bannerVisibilityCache[$userId];
		}

		$option = \CUserOptions::GetOption(
			'sign',
			"~sign_onboarding_signing_banner",
			null,
			$userId
		);

		self::$bannerVisibilityCache[$userId] = $option;

		return $option;
	}

	private function setBannerVisibilityOption(int $userId, string $value): void
	{
		\CUserOptions::SetOption(
			'sign',
			"~sign_onboarding_signing_banner",
			$value,
			false,
			$userId
		);

		self::$bannerVisibilityCache[$userId] = $value;
	}
}
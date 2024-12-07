<?php

declare(strict_types=1);

namespace Bitrix\Mobile\Config;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\MobileApp\Mobile;

abstract class FeatureFlag
{
	abstract public function isEnabled(): bool;

	abstract public function enable(): void;

	abstract public function disable(): void;

	public function isDisabled(): bool
	{
		return !static::isEnabled();
	}

	protected function getCurrentUserId(): ?int
	{
		$id = (int)CurrentUser::get()->getId();

		return $id > 0 ? $id : null;
	}

	protected function clientHasApiVersion(int $apiVersion): bool
	{
		return Mobile::getApiVersion() >= $apiVersion;
	}

	protected function clientUsesDeveloperAppBuild(): bool
	{
		return (bool)Mobile::getInstance()::$isDev;
	}
}

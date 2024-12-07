<?php

namespace Bitrix\IntranetMobile;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\MobileApp\Mobile;

final class Settings
{
	public const IS_BETA_AVAILABLE = 'isBetaAvailable';
	public const IS_BETA_ACTIVE = 'isBetaActive';

	protected int $userId;
	private string $clearMenuOptionName;

	private static ?Settings $instance = null;

	public static function getInstance(): Settings
	{
		if (!Settings::$instance)
		{
			Settings::$instance = new Settings();
		}

		return Settings::$instance;
	}

	private function __construct(?int $userId = null)
	{
		if (!$userId)
		{
			$userId = CurrentUser::get()->getId();
		}

		$this->userId = $userId;
		$this->clearMenuOptionName = "clear_more_$this->userId";
	}

	public function clientHasApiVersion(int $apiVersion): bool
	{
		return Mobile::getInstance()::getApiVersion() >= $apiVersion;
	}

	public function isBetaAvailable(): bool
	{
		return (
			Mobile::getInstance()::$isDev
			|| (
				$this->clientHasApiVersion(54)
				&& Option::get('intranetmobile', Settings::IS_BETA_AVAILABLE, 'N', '-') === 'Y'
			)
		);
	}

	public function isBetaActive(): bool
	{
		if ($this->isBetaAvailable())
		{
			return \CUserOptions::GetOption('intranetmobile', Settings::IS_BETA_ACTIVE, false, $this->userId);
		}

		return false;
	}

	public function activateBeta()
	{
		\CUserOptions::SetOption('intranetmobile', Settings::IS_BETA_ACTIVE, true, false, $this->userId);
	}

	public function deactivateBeta()
	{
		\CUserOptions::SetOption('intranetmobile', Settings::IS_BETA_ACTIVE, false, false, $this->userId);
	}
}

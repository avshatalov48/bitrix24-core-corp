<?php

namespace Bitrix\Crm\Terminal\Config;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Type\Collection;

final class PaysystemConfig
{
	// Enabled or disabled paysystems - only in terminal context
	private const LINK_PAYMENT_OPTION_NAME = 'terminal_is_link_payment_enabled';
	private const IS_RU_ZONE_OPTION_NAME = 'terminal_is_ru_zone';
	private const DISABLED_PAYSYSTEMS_OPTION_NAME = 'terminal_disabled_paysystems';
	private const IS_SBP_ENABLED_OPTION_NAME = 'terminal_is_sbp_enabled';
	private const IS_SBER_QR_ENABLED_OPTION_NAME = 'terminal_is_sber_qr_enabled';
	private const RU_ZONE = 'ru';
	private const TERMINAL_PAYMENT_SYSTEMS_COLLAPSED = 'terminal_payment_systems_collapsed';

	private static ?PaysystemConfig $instance = null;

	public static function getInstance(): self
	{
		if (!self::$instance)
		{
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function isLinkPaymentEnabled(): bool
	{
		return Option::get('crm', self::LINK_PAYMENT_OPTION_NAME, 'Y') === 'Y';
	}

	public function isSbpEnabled(): bool
	{
		if (!$this->isRuZone())
		{
			return false;
		}

		return Option::get('crm', self::IS_SBP_ENABLED_OPTION_NAME, 'Y') === 'Y';
	}

	public function isSberQrEnabled(): bool
	{
		if (!$this->isRuZone())
		{
			return false;
		}

		return Option::get('crm', self::IS_SBER_QR_ENABLED_OPTION_NAME, 'Y') === 'Y';
	}

	public function getTerminalDisabledPaysystems(): array
	{
		$jsonPaysystems = Option::get('crm', self::DISABLED_PAYSYSTEMS_OPTION_NAME);
		$paysystems = json_decode($jsonPaysystems, true);
		if (is_array($paysystems))
		{
			Collection::normalizeArrayValuesByInt($paysystems);

			return $paysystems;
		}

		return [];
	}

	public function setDisabledPaysystems(array $paysystems): void
	{
		Option::set('crm', self::DISABLED_PAYSYSTEMS_OPTION_NAME, json_encode($paysystems));
	}

	public function enableAllPaysystems(): void
	{
		$this->setDisabledPaysystems([]);
	}

	public function setLinkPaymentEnabled(bool $available): void
	{
		Option::set('crm', self::LINK_PAYMENT_OPTION_NAME, $available ? 'Y' : 'N');
	}

	public function setSbpEnabled(bool $available): void
	{
		Option::set('crm', self::IS_SBP_ENABLED_OPTION_NAME, $available ? 'Y' : 'N');
	}

	public function setSberQrEnabled(bool $available): void
	{
		Option::set('crm', self::IS_SBER_QR_ENABLED_OPTION_NAME, $available ? 'Y' : 'N');
	}

	public function setCollapsed(bool $status): void
	{
		\CUserOptions::SetOption('crm', self::TERMINAL_PAYMENT_SYSTEMS_COLLAPSED, $status ? 'Y' : 'N');
	}

	public function isCollapsed(): bool
	{
		return \CUserOptions::GetOption('crm', self::TERMINAL_PAYMENT_SYSTEMS_COLLAPSED, 'N') === 'Y';
	}

	public function isRuZone(): bool
	{
		$result = Option::get('crm', self::IS_RU_ZONE_OPTION_NAME, null);
		if ($result === 'Y')
		{
			return true;
		}

		if ($result === 'N')
		{
			return false;
		}

		$zone = null;
		if (ModuleManager::isModuleInstalled('bitrix24'))
		{
			$zone = \CBitrix24::getPortalZone();
		}
		elseif (Loader::includeModule('intranet'))
		{
			$zone = \CIntranetUtils::getPortalZone();
		}

		return $zone === self::RU_ZONE;
	}
}

<?php

namespace Bitrix\Crm\Settings;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

class RecyclebinSettings
{
	public const
		REMOVE_DAYS_NEVER = -1,
		REMOVE_DAYS_30 = 30,
		REMOVE_DAYS_60 = 60,
		REMOVE_DAYS_90 = 90;

	/** @var RecyclebinSettings */
	private static $current = null;
	/** @var BooleanSetting */
	private $ttl = null;
	private $isB24 = null;
	private static $ttlValues = null;
	private static $messagesLoaded = false;

	public function __construct()
	{
		$this->ttl = new IntegerSetting('recyclebin_ttl', self::REMOVE_DAYS_30);
		$this->isB24 = ModuleManager::isModuleInstalled('bitrix24');
	}

	/**
	 * @return RecyclebinSettings
	 */
	public static function getCurrent(): RecyclebinSettings
	{
		if(self::$current === null)
		{
			self::$current = new RecyclebinSettings();
		}
		return self::$current;
	}

	/**
	 * @return array
	 */
	public static function getTtlValues(): array
	{
		if(!self::$ttlValues)
		{
			self::includeModuleFile();

			self::$ttlValues = [
				self::REMOVE_DAYS_30 => Loc::getMessage('CRM_RECYCLEBIN_TTL_SETTINGS_QUANTITY_DAYS_REMOVE', ['#DAYS#' => 30]),
				self::REMOVE_DAYS_60 => Loc::getMessage('CRM_RECYCLEBIN_TTL_SETTINGS_QUANTITY_DAYS_REMOVE', ['#DAYS#' => 60]),
				self::REMOVE_DAYS_90 => Loc::getMessage('CRM_RECYCLEBIN_TTL_SETTINGS_QUANTITY_DAYS_REMOVE', ['#DAYS#' => 90]),
				self::REMOVE_DAYS_NEVER => Loc::getMessage('CRM_RECYCLEBIN_TTL_SETTINGS_NEVER_REMOVE')
			];
		}
		return self::$ttlValues;
	}

	protected static function includeModuleFile(): void
	{
		if(self::$messagesLoaded)
		{
			return;
		}

		Main\Localization\Loc::loadMessages(__FILE__);
		self::$messagesLoaded = true;
	}

	/**
	 * @return int
	 */
	public function getTtl(): int
	{
		return $this->ttl->get();
	}

	/**
	 * @param int $ttl
	 */
	public function setTtl(int $ttl): void
	{
		if($ttl >= 0 && $this->ttl->get() < 0)
		{
			$this->addAgent();
		}
		else if($ttl < 0)
		{
			$this->removeAgent();
		}
		$this->ttl->set($ttl);
	}

	private function addAgent(): void
	{
		if (!$this->isB24)
		{
			\CAgent::AddAgent('\Bitrix\Crm\Agent\Recyclebin\RecyclebinAgent::run();', 'crm', 'N', 3600);
		}
	}

	private function removeAgent(): void
	{
		if (!$this->isB24)
		{
			\CAgent::RemoveAgent('Bitrix\\Crm\\Agent\\Recyclebin\\RecyclebinAgent::run();', 'crm');
		}
	}
}
<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage bitrix24
 * @copyright 2001-2023 Bitrix
 */

namespace Bitrix\Intranet;

use Bitrix\Main;
use Bitrix\Intranet;
use Bitrix\Bitrix24;

class Portal
{
	protected static Portal $instance;

	protected Intranet\PortalSettings $settings;

	protected function __construct(?string $siteId = null)
	{
		$this->settings = Main\Loader::includeModule('bitrix24') ?
			Bitrix24\PortalSettings::getInstance() : Intranet\PortalSettings::getInstance()
		;
	}

	public function getSettings(): Intranet\PortalSettings
	{
		return $this->settings;
	}

	final public static function getInstance(): self
	{
		if (!isset(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}
}
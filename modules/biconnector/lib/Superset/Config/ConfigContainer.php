<?php

namespace Bitrix\BIConnector\Superset\Config;

class ConfigContainer
{
	private static self $configContainer;

	private const PROXY_REGION_OPTION = '~superset_config_proxy_region';

	private const PORTAL_ID_OPTION = '~superset_config_portal_id';
	private const PORTAL_ID_VERIFIED = '~superset_config_portal_id_verified';


	public static function getConfigContainer(): self
	{
		if (!isset(self::$configContainer))
		{
			self::$configContainer = new self();
		}

		return self::$configContainer;
	}

	public function setProxyRegion(string $region): void
	{
		\Bitrix\Main\Config\Option::set('biconnector', self::PROXY_REGION_OPTION, $region);
	}

	public function getProxyRegion(): string
	{
		return \Bitrix\Main\Config\Option::get('biconnector', self::PROXY_REGION_OPTION, '');
	}

	private function clearProxyRegion(): void
	{
		\Bitrix\Main\Config\Option::delete('biconnector', ['name' => self::PROXY_REGION_OPTION]);
	}

	public function getPortalId(): string
	{
		return \Bitrix\Main\Config\Option::get('biconnector', self::PORTAL_ID_OPTION, '');
	}

	public function setPortalId(string $clientId): void
	{
		$this->setPortalIdVerified(false);
		\Bitrix\Main\Config\Option::set('biconnector', self::PORTAL_ID_OPTION, $clientId);
	}

	private function clearPortalId(): void
	{
		$this->setPortalIdVerified(false);
		\Bitrix\Main\Config\Option::delete('biconnector', ['name' => self::PORTAL_ID_OPTION]);
	}

	public function setPortalIdVerified(bool $verify): void
	{
		\Bitrix\Main\Config\Option::set('biconnector', self::PORTAL_ID_VERIFIED, $verify ? 'Y' : 'N');
	}

	public function isPortalIdVerified(): bool
	{
		return \Bitrix\Main\Config\Option::get('biconnector', self::PORTAL_ID_VERIFIED, 'N') === 'Y';
	}

	public function clearConfig(): void
	{
		$this->clearPortalId();
		$this->clearProxyRegion();
	}
}

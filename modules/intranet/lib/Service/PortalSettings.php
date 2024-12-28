<?php

namespace Bitrix\Intranet\Service;

use Bitrix\Intranet\Portal\Settings\Logo24Settings;
use Bitrix\Intranet\Portal\Settings\LogoSettings;
use Bitrix\Intranet\Portal\Settings\PortalNameSettings;
use Bitrix\Intranet\Portal\Settings\PortalTitleSettings;
use Bitrix\Main\DI\ServiceLocator;

class PortalSettings
{
	private static array $instances = [];
	private ServiceLocator $serviceLocator;

	private function __construct()
	{
		$this->serviceLocator = ServiceLocator::getInstance();
	}

	final public static function getInstance(): static
	{
		if (!isset(static::$instances[static::class]))
		{
			self::$instances[static::class] = new static();
		}

		return self::$instances[static::class];
	}

	protected function getLocator(): ServiceLocator
	{
		return $this->serviceLocator;
	}

	protected function getPrefix(): string
	{
		return 'intranet.portal.settings.';
	}

	protected function get(string $id): mixed
	{
		return $this->getLocator()->get($this->getPrefix() . $id);
	}

	public function nameSettings(): PortalNameSettings
	{
		return $this->get('name');
	}

	public function titleSettings(): PortalTitleSettings
	{
		return $this->get('title');
	}

	public function logoSettings(): LogoSettings
	{
		return $this->get('logo');
	}

	public function logo24Settings(): Logo24Settings
	{
		return $this->get('logo24');
	}
}

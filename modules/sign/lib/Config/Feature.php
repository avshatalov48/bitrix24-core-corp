<?php

namespace Bitrix\Sign\Config;

use Bitrix\Main\Config\Option;
use Bitrix\Main;

final class Feature
{
	public const SIGN_COLLAB_INTEGRATION_ENABLED_OPTION = 'SIGN_COLLAB_INTEGRATION_ENABLED';
	private static ?self $instance = null;

	public static function instance(): self
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function isSendDocumentByEmployeeEnabled(?string $region = null): bool
	{
		$region = $region ?? Main\Application::getInstance()->getLicense()->getRegion();

		$publicitySettings = $this->read('service.b2e.init-by-employee.publicity');
		if (!is_array($publicitySettings))
		{
			return false;
		}

		$isPublic = (bool)($publicitySettings[$region] ?? false);
		if (!$isPublic)
		{
			return false;
		}

		return Storage::instance()->isB2eAvailable();
	}

	public function isCollabIntegrationEnabled(): bool
	{
		return
			Option::get('sign', self::SIGN_COLLAB_INTEGRATION_ENABLED_OPTION, true)
			&& Storage::instance()->isAvailable()
		;
	}

	public function enableOption(string $name): void
	{
		Option::set('sign', $name, true);
	}

	public function disableOption(string $name): void
	{
		Option::set('sign', $name, false);
	}

	public function isSenderTypeAvailable(): bool
	{
		return Option::get("sign", "is_sender_type_available", 'N') === 'Y';
	}

	public function isMultiDocumentLoadingEnabled(): bool
	{
		return Option::get("sign", "is_multi_document_loading_enabled", 'N') === 'Y';
	}

	public function isGroupSendingEnabled(): bool
	{
		return Option::get("sign", "is_group_sending_enabled", 'N') === 'Y';
	}

	private function read(string $name): mixed
	{
		$value = Main\Config\Configuration::getValue('sign')[$name] ?? null;
		if ($value !== null)
		{
			return $value;
		}

		return Main\Config\Configuration::getInstance('sign')->get($name);
	}

}

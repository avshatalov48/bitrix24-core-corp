<?php

namespace Bitrix\Disk\Document\Flipchart;

use Bitrix\Disk\Controller\Integration\Flipchart;
use Bitrix\Main\Config;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\IO\File;

class Configuration
{
	private static $localValues = null;

	private static function loadLocalValues(): void
	{
		self::$localValues = [];
		$path = $_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/disk-boards.php";
		if (File::isFileExists($path))
		{
			$localValues = require($path);
			if (is_array($localValues))
			{
				self::$localValues = $localValues;
			}
		}
	}

	private static function getFromSettings(string $name, $default = null)
	{
		if (is_null(self::$localValues))
		{
			self::loadLocalValues();
		}

		$localValue = self::$localValues[$name] ?? null;
		$boardsConfigPrimary = Config\Configuration::getInstance()->get('boards');
		$boardsConfig = Config\Configuration::getInstance('disk')->get('boards');

		return $localValue ?? $boardsConfigPrimary[$name] ?? $boardsConfig[$name] ?? $default;
	}

	public static function isBoardsEnabled(): bool
	{
		return Option::get('disk', 'boards_enabled', 'N') === 'Y';
	}

	public static function getClientTokenHeaderLookup(): string
	{
		$default = self::getFromSettings('client_token_header_lookup', 'X-Permissions');

		return Option::get('disk', 'flipchart.client_token_header_lookup', $default);
	}

	public static function getApiHost(): string
	{
		$default = self::getFromSettings('api_host', 'https://flip-backend');

		return Option::get('disk', 'flipchart.api_host', $default);
	}

	public static function getJwtSecret(): string
	{
		$default = self::getFromSettings('jwt_secret', 'secret_token');

		return Option::get('disk', 'flipchart.jwt_secret', $default);
	}

	public static function getJwtTtl(): int
	{
		$default = self::getFromSettings('jwt_ttl', 30);

		return (int)Option::get('disk', 'flipchart.jwt_ttl', $default);
	}

	public static function getAppUrl(): string
	{
		$default = self::getFromSettings('app_url', 'https://flip_backend/app');

		return Option::get('disk', 'flipchart.app_url', $default);
	}

	public static function getSaveDeltaTime(): int
	{
		$default = self::getFromSettings('save_delta_time', 30);

		return (int)Option::get('disk', 'flipchart.save_delta_time', $default);
	}

	public static function getSaveProbabilityCoef(): float
	{
		$default = self::getFromSettings('save_probability_coef', 0.1);

		return (float)Option::get('disk', 'flipchart.save_probability_coef', $default);
	}

	public static function getDocumentIdSalt(): string
	{
		$default = self::getFromSettings(
			'document_id_salt',
			crc32(
				\defined('BX24_DB_NAME')
					? BX24_DB_NAME
					: UrlManager::getInstance()->getHostUrl()
			)
		);

		return (string)Option::get('disk', 'flipchart.document_id_salt', $default);
	}

	/**
	 * @see Flipchart::webhookAction()
	 */
	public static function getWebhookUrl(): string
	{
		$default = self::getFromSettings(
			'webhook_url',
			'/bitrix/services/main/ajax.php?action=disk.integration.flipchart.webhook'
		);

		$urlManager = UrlManager::getInstance();
		$webhookUrl = $urlManager->getHostUrl() . $default;

		return Option::get('disk', 'flipchart.webhook_url', $webhookUrl);
	}
}
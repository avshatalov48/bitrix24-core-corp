<?php

namespace Bitrix\Disk\Document\Flipchart;

use Bitrix\Disk\Controller\Integration\Flipchart;
use Bitrix\Disk\Driver;
use Bitrix\Main\Config;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\IO\File;
use Bitrix\Main\Web\Json;

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

	public static function isUsingDocumentProxy(): bool
	{
		return Option::get('disk', 'boards_use_documentproxy', 'N') === 'Y';
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
		$default = self::getFromSettings('app_url', 'https://flip-backend/app');

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

	public static function getAllowedLanguages(): array
	{
		$default = self::getFromSettings('allowed_languages', [
			'ar',
			'br',
			'en',
			'fr',
			'id',
			'it',
			'ja',
			'la',
			'ms',
			'pl',
			'ru',
			'sc',
			'tc',
			'th',
			'tr',
			'ua',
			'vn',
			'de',
			'kz',
		]);

		$option = Option::get('disk', 'flipchart.allowed_languages');
		if (!$option)
		{
			return (array)$default;
		}

		return (array)Json::decode($option);
	}

	public static function getDefaultLanguage(): string
	{
		$default = self::getFromSettings('default_language', 'en');

		return (string)Option::get('disk', 'flipchart.default_language', $default);
	}

	public static function isForceHttpForDocumentUrl(): bool
	{
		$default = self::getFromSettings('force_http_for_document_url', 'N');

		return Option::get('disk', 'flipchart.force_http_for_document_url', $default) === 'Y';
	}

	public static function isReloadBoardAfterInactivityEnabled(): bool
	{
		$default = self::getFromSettings('reload_board_after_inactivity', 'Y');

		return Option::get('disk', 'flipchart.reload_board_after_inactivity', $default) === 'Y';
	}

	/**
	 * @return int Delay in ms (15 minutes by default)
	 */
	public static function getReloadBoardAfterInactivityDelay(): int
	{
		$default = self::getFromSettings('reload_board_after_inactivity_delay', 1000 * 60 * 15);

		return (int)Option::get('disk', 'flipchart.reload_board_after_inactivity_delay', $default);
	}

	/**
	 * @param array{clientId: string, secretKey: string, serverHost: string} $data
	 * @return void
	 */
	public function storeCloudRegistration(array $data): void
	{
		if (!isset($data['clientId'], $data['secretKey'], $data['serverHost']))
		{
			return;
		}

		Option::set(Driver::INTERNAL_MODULE_ID, 'disk_boards_b24_clientId', $data['clientId']);
		Option::set(Driver::INTERNAL_MODULE_ID, 'disk_boards_b24_secretKey', $data['secretKey']);
		Option::set(Driver::INTERNAL_MODULE_ID, 'disk_boards_b24_serverHost', $data['serverHost']);
	}

	/**
	 * @return null|array{clientId: string, secretKey: string, serverHost: string}
	 */
	public static function getCloudRegistrationData(): ?array
	{
		$data = array_filter([
			'clientId' => Option::get(Driver::INTERNAL_MODULE_ID, 'disk_boards_b24_clientId'),
			'secretKey' => Option::get(Driver::INTERNAL_MODULE_ID, 'disk_boards_b24_secretKey'),
			'serverHost' => Option::get(Driver::INTERNAL_MODULE_ID, 'disk_boards_b24_serverHost'),
		]);

		if (count($data) === 3)
		{
			return $data;
		}

		return null;
	}

	public function resetTempSecretForDomainVerification(): void
	{
		Option::set(Driver::INTERNAL_MODULE_ID, 'disk_boards_temp_secret', null);
	}

	public function storeTempSecretForDomainVerification(string $value): void
	{
		Option::set(Driver::INTERNAL_MODULE_ID, 'disk_boards_temp_secret', $value);
	}

	public function getTempSecretForDomainVerification(): string
	{
		return Option::get(Driver::INTERNAL_MODULE_ID, 'disk_boards_temp_secret');
	}

	public function resetCloudRegistration(): void
	{
		Option::delete('disk', [
			'name' => 'disk_boards_b24_clientId',
		]);
		Option::delete('disk', [
			'name' => 'disk_boards_b24_secretKey',
		]);
		Option::delete('disk', [
			'name' => 'disk_boards_b24_serverHost',
		]);
	}
}
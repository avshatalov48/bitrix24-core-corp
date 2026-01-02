<?php

namespace Bitrix\Crm\WebForm;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Uri;
use \Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

class YandexCaptcha
{
	const MODULE_ID = 'crm';
	const OPTION_NAME = 'crm_yandex_captcha';

	private string $secret;

	private string $error = '';

	private HttpClient $httpClient;

	public function __construct($secret, HttpClient $httpClient = null)
	{
		if (empty($secret))
		{
			throw new SystemException('No secret provided');
		}

		if (!is_string($secret))
		{
			throw new SystemException('The provided secret must be a string');
		}

		$this->secret = $secret;
		if (!is_null($httpClient))
		{
			$this->httpClient = $httpClient;
		}
		else
		{
			$this->httpClient = new HttpClient();
		}
	}

	public function verify($response, $remoteIp = null): bool
	{
		$this->error = '';

		if (empty($response))
		{
			$this->error = Loc::getMessage('CRM_WEBFORM_YANDEX_ERROR_MISSING_INPUT_RESPONSE');

			return false;
		}

		$captchaCheckUri = new Uri('https://smartcaptcha.yandexcloud.net/validate');
		$captchaCheckUri->addParams([
			'ip' => $remoteIp ? $remoteIp : Context::getCurrent()->getServer()->get('REMOTE_ADDR'),
			'secret' => $this->secret,
			'token' => $response,
		]);

		try {
			$checkResult = $this->httpClient->get((string)$captchaCheckUri);
			$captchaResult = Json::decode($checkResult);

			if (
				!is_array($captchaResult)
				|| ($captchaResult['status'] ?? 'failed') !== 'ok'
			)
			{
				$this->error = Loc::getMessage('CRM_WEBFORM_YANDEX_ERROR_INVALID_INPUT_RESPONSE');

				return false;
			}
		}
		catch (\Exception $e)
		{
			$this->error = Loc::getMessage('CRM_WEBFORM_YANDEX_ERROR_UNKNOWN');

			return false;
		}

		return ($captchaResult['status'] ?? 'failed') === 'ok';
	}

	public function getError(): string
	{
		return $this->error;
	}

	public static function getDefaultKey(): ?string
	{
		return Option::get(self::MODULE_ID, self::OPTION_NAME . '_def_key');
	}

	public static function getDefaultSecret(): ?string
	{
		return Option::get(self::MODULE_ID, self::OPTION_NAME . '_def_secret');
	}

	public static function getKey(): ?string
	{
		return Option::get(self::MODULE_ID, self::OPTION_NAME . '_key');
	}

	public static function getSecret(): ?string
	{
		return Option::get(self::MODULE_ID, self::OPTION_NAME . '_secret');
	}

	public static function setKey($key, $secret): void
	{
		Option::set(self::MODULE_ID, self::OPTION_NAME . '_key', $key);
		Option::set(self::MODULE_ID, self::OPTION_NAME . '_secret', $secret);
	}
}
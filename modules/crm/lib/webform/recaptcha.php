<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\SystemException;

Loc::loadMessages(__FILE__);

/**
 * reCAPTCHA client.
 */
class ReCaptcha
{
	/**
	 * Version of this client library.
	 * @const string
	 */
	const VERSION = 'php_1.1.2';
	const MODULE_ID = 'crm';
	const OPTION_NAME = 'crm_recaptcha';

	/**
	 * Shared secret for the site.
	 * @var string
	 */
	private $secret;

	/**
	 * Error code.
	 * @var string
	 */
	private $error = '';

	/**
	 * Method used to communicate with service. Defaults to POST request.
	 * @var HttpClient
	 */
	private $httpClient;

	/**
	 * Create a configured instance to use the reCAPTCHA service.
	 *
	 * @param string $secret shared secret between site and reCAPTCHA server.
	 * @param HttpClient $httpClient method used to send the request. Defaults to POST.
	 * @throws SystemException if $secret is invalid
	 */
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

	/**
	 * Calls the reCAPTCHA siteverify API to verify whether the user passes
	 * CAPTCHA test.
	 *
	 * @param string $response The value of 'g-recaptcha-response' in the submitted form.
	 * @param string $remoteIp The end user's IP address.
	 * @return bool Verifying result.
	 */
	public function verify($response, $remoteIp = null)
	{
		$this->error = '';
		// Discard empty solution submissions
		if (empty($response))
		{
			$this->error = Loc::getMessage('CRM_WEBFORM_RECAPTCHA_ERROR_MISSING_INPUT_RESPONSE');
			return false;
		}

		$rawResponse = $this->httpClient->post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'secret' => $this->secret,
				'response' => $response,
				'remoteip' => $remoteIp ? $remoteIp : Context::getCurrent()->getServer()->get('REMOTE_ADDR'),
			)
		);
		$response = Json::decode($rawResponse);
		if (!empty($response['error-codes']))
		{
			switch ($response['error-codes'][0])
			{
				case 'invalid-input-response':
					$this->error = Loc::getMessage('CRM_WEBFORM_RECAPTCHA_ERROR_INVALID_INPUT_RESPONSE');
					break;

				case 'missing-input-response':
					$this->error = Loc::getMessage('CRM_WEBFORM_RECAPTCHA_ERROR_MISSING_INPUT_RESPONSE');
					break;

				case 'invalid-input-secret':
					$this->error = Loc::getMessage('CRM_WEBFORM_RECAPTCHA_ERROR_INVALID_INPUT_SECRET');
					break;

				case 'missing-input-secret':
					$this->error = Loc::getMessage('CRM_WEBFORM_RECAPTCHA_ERROR_MISSING_INPUT_SECRET');
					break;

				default:
					$this->error = Loc::getMessage('CRM_WEBFORM_RECAPTCHA_ERROR_UNKNOWN');
					break;
			}

		}

		return (bool) $response['success'];
	}

	/**
	 * Return error code
	 *
	 * @return string
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * Get default key
	 *
	 * @var string $lang Language id
	 * @return string
	 */
	public static function getJavascriptResource($lang = LANGUAGE_ID)
	{
		switch ($lang)
		{
			case 'pl': //polish
			case 'de': //german
			case 'fr': //french
				// use $lang
				break;

			case 'la': //spanish
				$lang = 'es';
				break;

			case 'br': //brasiliero
				$lang = 'pt';
				break;

			case 'ru': //russian
			case 'by':
			case 'kz':
				$lang = 'ru';
				break;

			case 'ua': //ukrainian
				$lang = 'uk';
				break;

			case 'sc': //Chinese simplified
				$lang = 'zh-TW';
				break;

			case 'tc': //chinese traditional
				$lang = 'zh-CN';
				break;

			case 'in': //india
			case 'en': //english
			default:
				$lang = 'en';
				break;
		}

		return 'https://www.google.com/recaptcha/api.js?onload=onReCaptchaLoadCallback&render=explicit&hl=' . $lang;
	}

	/**
	 * Get default key
	 *
	 * @return string
	 */
	public static function getDefaultKey()
	{
		return Option::get(self::MODULE_ID, self::OPTION_NAME . '_def_key');
	}

	/**
	 * Get default secret
	 *
	 * @return string
	 */
	public static function getDefaultSecret()
	{
		return Option::get(self::MODULE_ID, self::OPTION_NAME . '_def_secret');
	}

	/**
	 * Get key
	 *
	 * @return string
	 */
	public static function getKey()
	{
		return Option::get(self::MODULE_ID, self::OPTION_NAME . '_key');
	}

	/**
	 * Get secret
	 *
	 * @return string
	 */
	public static function getSecret()
	{
		return Option::get(self::MODULE_ID, self::OPTION_NAME . '_secret');
	}

	/**
	 * Set key & secret
	 *
	 * @param string $key Key
	 * @param string $secret Secret
	 * @return void
	 */
	public static function setKey($key, $secret)
	{
		Option::set(self::MODULE_ID, self::OPTION_NAME . '_key', $key);
		Option::set(self::MODULE_ID, self::OPTION_NAME . '_secret', $secret);
	}
}
<?php
namespace Bitrix\ImBot;

use Bitrix\Main;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

class Http
{
	const MODULE_ID = 'imbot';
	const BOT_ID = 'marta';

	const TYPE_BITRIX24 = 'B24';
	const TYPE_CP = 'CP';
	const VERSION = 1;

	public const ERROR_NETWORK = 'NETWORK_ERROR';
	public const ERROR_ANSWER = 'ANSWER_MALFORMED';

	private $controllerUrl = 'https://marta.bitrix.info/json/';
	private $licenceCode = '';
	private $domain = '';
	private $type = '';
	private $error = null;

	function __construct($botId)
	{
		$this->error = new Error(null, '', '');
		if (defined('BOT_CONTROLLER_URL'))
		{
			$this->controllerUrl = BOT_CONTROLLER_URL;
		}
		if(defined('BX24_HOST_NAME'))
		{
			$this->licenceCode = BX24_HOST_NAME;
		}
		else
		{
			require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");
			$this->licenceCode = md5("BITRIX".\CUpdateClient::GetLicenseKey()."LICENCE");
		}
		$this->type = self::getPortalType();
		$this->domain = self::getServerAddress();
		$this->botId = $botId;

		\Bitrix\Main\Loader::includeModule('im');

		return true;
	}

	public static function getPortalType()
	{
		if(defined('BX24_HOST_NAME'))
		{
			$type = self::TYPE_BITRIX24;
		}
		else
		{
			$type = self::TYPE_CP;
		}

		return $type;
	}

	/**
	 * Returns from settings or detects from request external public url.
	 *
	 * @return string
	 */
	public static function getServerAddress()
	{
		static $publicUrl;
		if ($publicUrl === null)
		{
			$publicUrl = Main\Config\Option::get(self::MODULE_ID, "portal_url");

			if (defined('BOT_CLIENT_URL'))
			{
				$publicUrl = \BOT_CLIENT_URL;
			}
			if (empty($publicUrl))
			{
				$context = Main\Application::getInstance()->getContext();
				$scheme = $context->getRequest()->isHttps() ? 'https' : 'http';
				$server = $context->getServer();
				$domain = Main\Config\Option::get('main', 'server_name', '');
				if (empty($domain))
				{
					$domain = $server->getServerName();
				}
				if (preg_match('/^(?<domain>.+):(?<port>\d+)$/', $domain, $matches))
				{
					$domain = $matches['domain'];
					$port   = $matches['port'];
				}
				else
				{
					$port = $server->getServerPort();
				}
				$port = in_array($port, array(80, 443)) ? '' : ':'.$port;

				$publicUrl = $scheme.'://'.$domain.$port;
			}
		}

		return $publicUrl;
	}


	public static function requestSign($type, $str)
	{
		if ($type == self::TYPE_BITRIX24 && function_exists('bx_sign'))
		{
			return bx_sign($str);
		}
		else
		{
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/license_key.php");
			/** @global string $LICENSE_KEY */
			return md5($str.md5($LICENSE_KEY));
		}
	}

	/**
	 * @param string $command
	 * @param array $params
	 * @param bool $waitResponse
	 *
	 * @return bool|array
	 * 	Returns controller answer as array in case of waitResponse=true else boolean.
	 * 	If error throws 'error' field will be added into result array in case of waitResponse=true else boolean false'll return. <pre>
	 * 	'error' => [
	 * 		'code' => self::ERROR_ANSWER | self::ERROR_NETWORK,
	 * 		'msg' => 'Error message',
	 * 		'errorResult' => 'Server answer',
	 * 		'errorStack' => [
	 * 			'errCode' => 'Real error message'
	 * 		],
	 * 	]
	 * </pre>
	 */
	public function query($command, $params = array(), $waitResponse = false)
	{
		if ($command == '' || !is_array($params) || !$this->botId)
		{
			return false;
		}

		foreach ($params as $key => $value)
		{
			$value = $value === "0"? "#ZERO#": $value;
			$params[$key] = empty($value)? '#EMPTY#': $value;
		}

		$params['BX_COMMAND'] = $command;
		$params['BX_BOT'] = $this->botId;
		$params['BX_LICENCE'] = $this->licenceCode;
		$params['BX_DOMAIN'] = $this->domain;
		$params['BX_TYPE'] = $this->type;
		$params['BX_VERSION'] = self::VERSION;
		$params['BX_LANG'] = \Bitrix\Im\Bot::getDefaultLanguage();
		$params = \Bitrix\Main\Text\Encoding::convertEncodingArray($params, SITE_CHARSET, 'UTF-8');
		$params["BX_HASH"] = self::requestSign($this->type, md5(implode("|", $params)));

		$waitResponse = $waitResponse? true: \Bitrix\Main\Config\Option::get("imbot", "wait_response");

		Log::write(Array($this->controllerUrl, $params), 'COMMAND: '.$command);

		$controllerUrl = $this->controllerUrl.'?';
		$controllerUrl .= 'BOT='.$this->botId.'&';
		$controllerUrl .= 'COMMAND='.$command;

		$httpClient = new \Bitrix\Main\Web\HttpClient(array(
			"socketTimeout" => 20,
			"streamTimeout" => 60,
			"waitResponse" => $waitResponse,
			"disableSslVerification" => true,
		));
		$httpClient->setHeader('User-Agent', 'Bitrix Bot Client ('.$this->botId.')');
		$httpClient->setHeader('x-bitrix-licence', $this->licenceCode);
		$httpClient->setHeader('x-bitrix-imbot', $this->botId);

		$result = $httpClient->post($controllerUrl, $params);

		if (defined('BOT_CONTROLLER_URL'))
		{
			Log::write(Array($result), 'COMMAND RESULT: '.$command);
		}

		if ($waitResponse)
		{
			if ($result === false)
			{
				// check for network errors
				$errors = $httpClient->getError();
				if (!empty($errors))
				{
					$result = [
						'error' => [
							'code' => self::ERROR_NETWORK,
							'msg' => 'Network connection error.',
							'errorStack' => $errors,
						]
					];
				}
			}
			// try to parse result
			elseif (is_string($result))
			{
				try
				{
					$result = Json::decode($result);
				}
				catch (ArgumentException $exception)
				{
					$result = [
						'error' => [
							'code' => self::ERROR_ANSWER,
							'msg' => 'Server answer is malformed.',
							'errorResult' => $result,
							'errorStack' => [
								$exception->getCode() => $exception->getMessage()
							],
						]
					];
				}
			}
		}
		// don't wait for response body
		else
		{
			$result = ($result !== false);
		}

		return $result;
	}

	/**
	 * @param int $dialogId
	 * @param int $messageId
	 * @param string $messageText
	 * @param string $userName
	 * @param int $userAge
	 *
	 * @return array|bool
	 */
	public function sendMessage($dialogId, $messageId, $messageText, $userName, $userAge = 30)
	{
		$params = Array(
			'DIALOG_ID' => $dialogId,
			'MESSAGE_ID' => $messageId,
			'MESSAGE_TEXT' => $messageText,
			'USER_NAME' => $userName,
			'USER_AGE' => $userAge
		);

		$query = $this->query(
			'SendMessage',
			$params
		);
		if (is_array($query) && isset($query['error']))
		{
			$this->error = new Error(__METHOD__, $query['error']['code'], $query['error']['msg']);
			return false;
		}

		return $query;
	}

	/**
	 * @return Error
	 */
	public function getError()
	{
		return $this->error;
	}
}
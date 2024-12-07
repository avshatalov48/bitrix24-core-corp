<?php
namespace Bitrix\ImBot;

use Bitrix\Main;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Config\Option;


class Http
{
	public const
		TYPE_BITRIX24 = 'B24',
		TYPE_CP = 'CP';

	public const
		ERROR_NETWORK = 'NETWORK_ERROR',
		ERROR_ANSWER = 'ANSWER_MALFORMED';

	private const SERVICE_MAP = [
		'ru' => 'https://marta.bitrix.info/json/',
		'eu' => 'https://marta-eu.bitrix.info/json/',
	];

	/** @var string */
	private $licenceCode = '';

	/** @var string */
	private $domain = '';

	/** @var string */
	private $region = '';

	/** @var string */
	private $type = '';

	/** @var string */
	private $botId = '';

	/** @var string */
	private $controllerUrl = '';

	/** @var Error */
	private $error;


	public function __construct($botId)
	{
		$this->botId = $botId;
		$this->error = new Error(null, '', '');
		$this->region = Main\Application::getInstance()->getLicense()->getRegion() ?: 'ru';
		$this->setControllerUrl(self::getServiceEndpoint($this->region));
		$this->licenceCode = $this->detectLicenceCode();
		$this->type = $this->detectPortalType();
		$this->setPortalDomain(self::getServerAddress());

		\Bitrix\Main\Loader::includeModule('im');
	}

	/**
	 * Returns controller service endpoint url.
	 *
	 * @param string $region Portal region.
	 * @return string
	 */
	public static function getServiceEndpoint(string $region): string
	{
		if (defined('BOT_CONTROLLER_URL'))
		{
			$serviceEndpoint = \BOT_CONTROLLER_URL;
		}
		else
		{
			if (in_array($region, ['ru', 'by', 'kz'], true))
			{
				$serviceEndpoint = self::SERVICE_MAP['ru'];
			}
			else
			{
				$serviceEndpoint = self::SERVICE_MAP['eu'];
			}
		}

		return $serviceEndpoint;
	}

	/**
	 * Returns from settings or detects from request external public url.
	 *
	 * @return string
	 */
	public static function getServerAddress(): string
	{
		static $publicUrl;
		if ($publicUrl === null)
		{
			$publicUrl = Option::get('imbot', 'portal_url');

			if (defined('BOT_CLIENT_URL'))
			{
				$publicUrl = \BOT_CLIENT_URL;
			}
			if (empty($publicUrl))
			{
				$context = Main\Application::getInstance()->getContext();
				$scheme = $context->getRequest()->isHttps() ? 'https' : 'http';
				$server = $context->getServer();
				$domain = Option::get('main', 'server_name', '');
				if (empty($domain))
				{
					$domain = $server->getServerName();
				}
				if (preg_match('/^(?<domain>.+):(?<port>\d+)$/', $domain, $matches))
				{
					$domain = $matches['domain'];
					$port = (int)$matches['port'];
				}
				else
				{
					$port = (int)$server->getServerPort();
				}
				$port = in_array($port, [0, 80, 443]) ? '' : ':'.$port;

				$publicUrl = $scheme.'://'.$domain.$port;
			}
			if (!(mb_strpos($publicUrl, 'https://') === 0 || mb_strpos($publicUrl, 'http://') === 0))
			{
				$publicUrl = 'https://' . $publicUrl;
			}
		}

		return $publicUrl;
	}

	/**
	 * @param string $type
	 * @param array $params
	 * @return string
	 */
	public static function requestSign(string $type, array $params): string
	{
		$sign = '';

		$params2 = [];
		foreach ($params as $val)
		{
			if (is_array($val))
			{
				$params2[] = 'Array';
			}
			else
			{
				$params2[] = $val;
			}
		}

		$str = \md5(implode('|', $params2));

		if ($type == self::TYPE_BITRIX24 && function_exists('bx_sign'))
		{
			$sign = \bx_sign($str);
		}
		else
		{
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/license_key.php");
			/** @global string $LICENSE_KEY */
			$sign = \md5($str. \md5($LICENSE_KEY));
		}

		return $sign;
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
			$value = $value === '0' ? '#ZERO#' : $value;
			$params[$key] = empty($value) ? '#EMPTY#' : $value;
		}

		$params['BX_COMMAND'] = $command;
		$params['BX_BOT'] = $this->botId;
		$params['BX_LICENCE'] = $this->licenceCode;
		$params['BX_DOMAIN'] = $this->domain;
		$params['BX_TYPE'] = $this->type;
		$params['BX_REGION'] = $this->region;
		$params['BX_VERSION'] = Main\ModuleManager::getVersion('imbot');
		$params['BX_LANG'] = \Bitrix\Main\Localization\Loc::getCurrentLang();
		$params['BX_HASH'] = self::requestSign($this->type, $params);

		$waitResponse = $waitResponse ? true : (bool)Option::get('imbot', 'wait_response', false);

		Log::write([$this->controllerUrl, $params], 'COMMAND: '.$command);

		$controllerUrl = $this->controllerUrl.'?';
		$controllerUrl .= 'BOT='.$this->botId.'&';
		$controllerUrl .= 'COMMAND='.$command;

		$httpClient = $this->instanceHttpClient($waitResponse);

		$result = $httpClient->post($controllerUrl, $params);
		$errorCode = $httpClient->getHeaders()->get('x-bitrix-error');

		Log::write(['response' => $result, 'error' => $errorCode], 'COMMAND RESULT: '.$command);

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
		elseif ($waitResponse)
		{
			// try to parse result
			if (is_string($result))
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

			if ($errorCode)
			{
				$result = [
					'error' => [
						'code' => $errorCode,
					]
				];
			}
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
	public function getError(): Error
	{
		return $this->error;
	}

	/**
	 * @param string $url
	 * @return $this
	 */
	public function setControllerUrl(string $url): self
	{
		$this->controllerUrl = $url;
		return $this;
	}

	/**
	 * @param string $licence
	 * @return $this
	 */
	public function setLicenceCode(string $licence): self
	{
		$this->licenceCode = $licence;
		return $this;
	}

	/**
	 * Returns the portal's licence code.
	 * @return string
	 */
	private function detectLicenceCode(): string
	{
		if (defined('BX24_HOST_NAME'))
		{
			$licenceCode = \BX24_HOST_NAME;
		}
		else
		{
			$licenceCode = Main\Application::getInstance()->getLicense()->getPublicHashKey();
		}

		return $licenceCode;
	}

	/**
	 * @param string $type
	 * @return $this
	 */
	public function setPortalType(string $type): self
	{
		$this->type = $type;
		return $this;
	}

	/**
	 * Returns the kind of portal installation.
	 * @return string
	 */
	private function detectPortalType(): string
	{
		if (defined('BX24_HOST_NAME'))
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
	 * @param string $domain
	 * @return $this
	 */
	public function setPortalDomain(string $domain): self
	{
		$this->domain = $domain;
		return $this;
	}

	/**
	 * @param string $botCode
	 * @return $this
	 */
	public function setBotCode(string $botCode): self
	{
		$this->botId = $botCode;
		return $this;
	}

	/**
	 * @return HttpClient
	 */
	public function instanceHttpClient(bool $waitResponse = false): HttpClient
	{
		$httpClient = new HttpClient();
		$httpClient
			->waitResponse($waitResponse)
			->setTimeout(20)
			->setStreamTimeout(60)
			->disableSslVerification()
			->setHeader('User-Agent', 'Bitrix Bot Client ('.$this->botId.')')
			->setHeader('x-bitrix-licence', $this->licenceCode)
			->setHeader('x-bitrix-imbot', $this->botId)
		;

		return $httpClient;
	}
}
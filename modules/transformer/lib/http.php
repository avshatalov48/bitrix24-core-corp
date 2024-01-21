<?php
namespace Bitrix\Transformer;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Bitrix\Transformer\Entity\CommandTable;

class Http
{
	const MODULE_ID = 'transformer';

	const TYPE_BITRIX24 = 'B24';
	const TYPE_CP = 'BOX';
	const VERSION = 1;

	const BACK_URL = '/bitrix/tools/transformer_result.php';

	const CONNECTION_ERROR = 'no connection with controller';

	/**
	 * @deprecated \Bitrix\Transformer\Http::getDefaultCloudControllerUrl
	 */
	public const CLOUD_CONVERTER_URL = 'https://transformer-de.bitrix.info/bitrix/tools/transformercontroller/add_queue.php';

	private $controllerUrl;
	private $licenceCode = '';
	private $domain = '';
	private $type = '';

	public function __construct()
	{
		$this->controllerUrl = self::getControllerUrl();

		if(defined('BX24_HOST_NAME'))
		{
			$this->licenceCode = BX24_HOST_NAME;
		}
		else
		{
			$this->licenceCode = Application::getInstance()->getLicense()->getPublicHashKey();
		}
		$this->type = self::getPortalType();
		$this->domain = self::getServerAddress();
	}

	private static function getControllerUrl(): ?string
	{
		if(defined('TRANSFORMER_CONTROLLER_URL'))
		{
			return TRANSFORMER_CONTROLLER_URL;
		}

		$optionsControllerUrl = Option::get(
			self::MODULE_ID,
			'transformer_controller_url',
			self::getDefaultCloudControllerUrl(),
		);
		if(!empty($optionsControllerUrl))
		{
			$uri = new Uri($optionsControllerUrl);
			if($uri->getHost())
			{
				return $uri->getLocator();
			}
		}

		return null;
	}

	/**
	 * @return string
	 */
	private static function getPortalType()
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
	 * @return string
	 */
	public static function getServerAddress()
	{
		$publicUrl = \Bitrix\Main\Config\Option::get(self::MODULE_ID, 'portal_url');

		if(!empty($publicUrl))
		{
			return $publicUrl;
		}

		return UrlManager::getInstance()->getHostUrl();
	}

	final public static function getDefaultCloudControllerUrl(): string
	{
		$region = Application::getInstance()->getLicense()->getRegion();

		return match ($region) {
			'ru' => 'https://transformer-ru-boxes.bitrix.info/bitrix/tools/transformercontroller/add_queue.php',
			default => self::CLOUD_CONVERTER_URL,
		};
	}

	/**
	 * @internal
	 */
	final public static function isDefaultCloudControllerUsed(): bool
	{
		return self::getControllerUrl() === self::getDefaultCloudControllerUrl();
	}

	/**
	 * Sign string with license or bx_sign.
	 *
	 * @param string $type Type of the license.
	 * @param string $str String to sign.
	 * @return string
	 */
	private static function requestSign($type, $str)
	{
		if($type == self::TYPE_BITRIX24 && function_exists('bx_sign'))
		{
			return bx_sign($str);
		}
		else
		{
			/** @var string $LICENSE_KEY */
			include($_SERVER['DOCUMENT_ROOT'].'/bitrix/license_key.php');
			return md5($str.md5($LICENSE_KEY));
		}
	}

	/**
	 * Add necessary parameters to post and send it to the controller.
	 *
	 * @param string $command Command to be executed.
	 * @param string $guid GUID of the command to form back url.
	 * @param array $params Parameters of the command.
	 * @return array|bool|mixed
	 * @throws ArgumentNullException
	 * @throws ArgumentTypeException
	 */
	public function query($command, $guid, $params = array())
	{
		if($command == '')
		{
			throw new ArgumentNullException('command');
		}
		if(!is_array($params))
		{
			throw new ArgumentTypeException('params', 'array');
		}

		$logContext = [
			'guid' => $guid,
			'controllerUrl' => $this->controllerUrl,
		];

		if(!$this->shouldWeSend())
		{
			Log::logger()->error(
				'Error sending command: too many unsuccessful attempts, send aborted',
				['errorCode' => Command::ERROR_CONNECTION_COUNT] + $logContext,
			);

			return [
				'success' => false,
				'result' => [
					'code' => Command::ERROR_CONNECTION_COUNT
				]
			];
		}

		if($params['file'])
		{
			$uri = new \Bitrix\Main\Web\Uri($params['file']);
			if($uri->getHost() == '')
			{
				$params['file'] = (new Uri($this->domain.$params['file']))->getLocator();
			}
		}

		$params['back_url'] = $this->getBackUrl($guid);

		$post = array('command' => $command, 'params' => $params);

		if($params['queue'])
		{
			$post['QUEUE'] = $params['queue'];
		}
		$post['BX_LICENCE'] = $this->licenceCode;
		$post['BX_DOMAIN'] = $this->domain;
		$post['BX_TYPE'] = $this->type;
		$post['BX_VERSION'] = self::VERSION;
		$post = \Bitrix\Main\Text\Encoding::convertEncoding($post, SITE_CHARSET, 'UTF-8');
		$post['BX_HASH'] = self::requestSign($this->type, md5(implode('|', $post)));

		$socketTimeout = Option::get(self::MODULE_ID, 'connection_time', 8);
		$streamTimeout = Option::get(self::MODULE_ID, 'stream_time', 8);

		$logContext += [
			'request' => $post,
			'socketTimeout' => $socketTimeout,
			'streamTimeout' => $streamTimeout,
		];
		Log::logger()->debug('Sending command to server', $logContext);

		$httpClient = new \Bitrix\Main\Web\HttpClient([
			'socketTimeout' => $socketTimeout,
			'streamTimeout' => $streamTimeout,
			'waitResponse' => true,
		]);
		$httpClient->setHeader('User-Agent', 'Bitrix Transformer Client');
		$httpClient->setHeader('Referer', $this->domain);
		$response = $httpClient->post($this->controllerUrl, $post);

		$logContext['response'] = $response;
		Log::logger()->debug(
			'Got response from server',
			$logContext,
		);

		if($response === false)
		{
			Log::logger()->error(
				'Error connecting to server',
				['errorCode' => Command::ERROR_CONNECTION] + $logContext
			);

			return [
				'success' => false,
				'result' => [
					'code' => Command::ERROR_CONNECTION
				]
			];
		}
		try
		{
			return Json::decode($response);
		}
		catch(ArgumentException $e)
		{
			Log::logger()->error(
				'Error decoding response from server',
				['error' => $e->getMessage(), 'errorCode' => Command::ERROR_CONNECTION_RESPONSE] + $logContext,
			);

			return [
				'success' => false,
				'result' => [
					'code' => Command::ERROR_CONNECTION_RESPONSE,
				]
			];
		}
	}

	/**
	 * Add 'id' parameter with real id of the command.
	 *
	 * @param int $id Id to find command from CommandTable on callback.
	 * @return string
	 */
	private function getBackUrl($id)
	{
		$uri = new Uri(self::BACK_URL);
		$uri->addParams(array('id' => $id));
		if($uri->getHost() == '')
		{
			$uri = (new Uri($this->domain.$uri->getPathQuery()))->getLocator();
		}
		return $uri;
	}

	private function shouldWeSend()
	{
		$timeSearchConnectionErrors = 1800;
		$errorCountForStopSend = 5;

		$errorCount = CommandTable::getList(array(
			'select' => array('CNT'),
			'filter' => array('=ERROR' => self::CONNECTION_ERROR, '>UPDATE_TIME' => DateTime::createFromTimestamp(time() - $timeSearchConnectionErrors)),
			'runtime' => array(
				new ExpressionField('CNT', 'COUNT(*)')
			),
		))->fetch();

		return ($errorCount['CNT'] < $errorCountForStopSend);
	}
}

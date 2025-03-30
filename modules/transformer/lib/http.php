<?php
namespace Bitrix\Transformer;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DI\ServiceLocator;
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

	/**
	 * @deprecated
	 */
	const CONNECTION_ERROR = 'no connection with controller';

	/**
	 * @deprecated \Bitrix\Transformer\Http\ControllerResolver::getDefaultCloudControllerUrl
	 */
	public const CLOUD_CONVERTER_URL = 'https://transformer-de.bitrix.info/bitrix/tools/transformercontroller/add_queue.php';

	public const CIRCUIT_BREAKER_ERRORS_THRESHOLD = 5;
	public const CIRCUIT_BREAKER_ERRORS_SEARCH_PERIOD = 1800;

	private $licenceCode = '';
	private $domain = '';
	private $type = '';

	public function __construct()
	{
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
	 * @return array
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

		$controllerUrl = ServiceLocator::getInstance()->get('transformer.http.controllerResolver')->resolveControllerUrl(
			$command,
			$params['queue'] ?? null,
		);

		$logContext = [
			'guid' => $guid,
			'controllerUrl' => $controllerUrl,
		];

		if (empty($controllerUrl))
		{
			return $this->logErrorAndReturnResponse(
				'Error sending command: controller url is empty',
				Command::ERROR_EMPTY_CONTROLLER_URL,
				$logContext,
				$controllerUrl,
			);
		}

		if (!$this->shouldWeSend($controllerUrl))
		{
			return $this->logErrorAndReturnResponse(
				'Error sending command: too many unsuccessful attempts, send aborted',
				Command::ERROR_CONNECTION_COUNT,
				$logContext,
				$controllerUrl
			);
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

		if(!empty($params['queue']))
		{
			$post['QUEUE'] = $params['queue'];
		}
		$post['BX_LICENCE'] = $this->licenceCode;
		$post['BX_DOMAIN'] = $this->domain;
		$post['BX_TYPE'] = $this->type;
		$post['BX_VERSION'] = self::VERSION;
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
		$response = $httpClient->post($controllerUrl, $post);

		$logContext['response'] = $response;
		Log::logger()->debug(
			'Got response from server',
			$logContext,
		);

		if($response === false)
		{
			return $this->logErrorAndReturnResponse(
				'Error connecting to server',
				Command::ERROR_CONNECTION,
				$logContext,
				$controllerUrl,
			);
		}

		try
		{
			$json = Json::decode($response);
			$decodeErrorMessage = null;
		}
		catch(ArgumentException $e)
		{
			$json = null;
			$decodeErrorMessage = $e->getMessage();
		}

		if (!is_array($json))
		{
			return $this->logErrorAndReturnResponse(
				'Error decoding response from server: {decodeError}',
				Command::ERROR_CONNECTION_RESPONSE,
				$logContext + ['decodeError' => $decodeErrorMessage],
				$controllerUrl,
			);
		}

		$json['controllerUrl'] = $controllerUrl;

		return $json;
	}

	private function logErrorAndReturnResponse(
		string $errorMessage,
		int $errorCode,
		array $logContext,
		?string $controllerUrl,
	): array
	{
		$logContext += ['errorCode' => $errorCode];

		Log::logger()->error($errorMessage, $logContext);

		return [
			'success' => false,
			'result' => [
				'msg' => $errorMessage,
				'code' => $errorCode,
			],
			'controllerUrl' => $controllerUrl,
		];
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

	private function shouldWeSend(string $controllerUrl): bool
	{
		static $secondsSearchConnectionErrors = self::CIRCUIT_BREAKER_ERRORS_SEARCH_PERIOD;

		$queryResult = CommandTable::query()
			->setSelect(['CNT'])
			->where('CONTROLLER_URL', $controllerUrl)
			->whereIn('ERROR_CODE', [Command::ERROR_CONNECTION, Command::ERROR_CONNECTION_RESPONSE])
			->where('UPDATE_TIME', '>', (new DateTime())->add("-T{$secondsSearchConnectionErrors}S"))
			->registerRuntimeField(
				new ExpressionField('CNT', 'COUNT(*)')
			)
			->fetch()
		;

		$errorCount = $queryResult['CNT'] ?? 0;

		return ($errorCount < self::CIRCUIT_BREAKER_ERRORS_THRESHOLD);
	}
}

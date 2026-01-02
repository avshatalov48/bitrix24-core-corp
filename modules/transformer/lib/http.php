<?php

namespace Bitrix\Transformer;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Service\MicroService\Client;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Bitrix\Transformer\Entity\CommandTable;

class Http
{
	public const MODULE_ID = 'transformer';

	/**
	 * @deprecated use Client::TYPE_BITRIX24
	 */
	public const TYPE_BITRIX24 = Client::TYPE_BITRIX24;
	/**
	 * @deprecated use Client::TYPE_BOX
	 */
	public const TYPE_CP = Client::TYPE_BOX;
	public const VERSION = 1;

	public const BACK_URL = '/bitrix/tools/transformer_result.php';

	/**
	 * @deprecated
	 */
	public const CONNECTION_ERROR = 'no connection with controller';

	public const CIRCUIT_BREAKER_ERRORS_THRESHOLD = 5;
	public const CIRCUIT_BREAKER_ERRORS_SEARCH_PERIOD = 1800;

	private string $domain = '';

	public function __construct()
	{
		$this->domain = self::getServerAddress();
	}

	/**
	 * @return string
	 */
	public static function getServerAddress()
	{
		$publicUrl = Option::get(self::MODULE_ID, 'portal_url');

		if (!empty($publicUrl))
		{
			return $publicUrl;
		}

		return UrlManager::getInstance()->getHostUrl();
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
	public function query($command, $guid, $params = [])
	{
		if ($command == '')
		{
			throw new ArgumentNullException('command');
		}
		if (!is_array($params))
		{
			throw new ArgumentTypeException('params', 'array');
		}

		$controllerUrl = ServiceLocator::getInstance()->get('transformer.service.http.controllerResolver')->resolveControllerUrl(
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

		if ($params['file'])
		{
			$uri = new Uri($params['file']);
			if ($uri->getHost() == '')
			{
				$params['file'] = (new Uri($this->domain.$params['file']))->getLocator();
			}
		}

		$params['back_url'] = $this->getBackUrl($guid);

		$post = [
			'command' => $command,
			'params' => $params,
			'BX_LICENCE' => Client::getLicenseCode(),
			'BX_DOMAIN' => $this->domain,
			'BX_TYPE' => Client::getPortalType(),
			'BX_VERSION' => self::VERSION,
			'BX_REGION' => \Bitrix\Main\Application::getInstance()->getLicense()->getRegion(),
		];

		if (!empty($params['queue']))
		{
			$post['QUEUE'] = $params['queue'];
		}

		$post['BX_HASH'] = Client::signRequest($post);

		$socketTimeout = Option::get(self::MODULE_ID, 'connection_time');
		$streamTimeout = Option::get(self::MODULE_ID, 'stream_time');

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

		if ($response === false)
		{
			$logContext['httpClientErrors'] = $httpClient->getError();

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
		}
		catch(ArgumentException $e)
		{
			$json = null;
			$logContext['decodeError'] = $e->getMessage();
		}

		if (!is_array($json))
		{
			return $this->logErrorAndReturnResponse(
				'Error decoding response from server: {decodeError}',
				Command::ERROR_CONNECTION_RESPONSE,
				$logContext,
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
		$uri->addParams(['id' => $id]);
		if ($uri->getHost() == '')
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

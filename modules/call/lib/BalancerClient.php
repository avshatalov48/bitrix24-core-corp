<?php

namespace Bitrix\Call;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Http\ClientException;
use Bitrix\Main\Web\Http\Method;
use Bitrix\Main\Web\Http\Request;
use Bitrix\Main\Web\Http\Stream;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\JWT;
use Bitrix\Main\Web\Uri;
use Psr\Http\Message\ResponseInterface;

class BalancerClient
{
	private const BALANCER_MAP = [
		'ru' => 'https://slb.bitrix24.tech',
		'eu' => 'https://slb-de.webrtc.bitrix.info',
		'us' => 'https://slb-us.webrtc.bitrix.info',
	];

	/**
	 * Returns call balancer service endpoint url.
	 *
	 * @param string $region Portal region.
	 * @return string
	 */
	public function getEndpoint(string $region): string
	{
		$endpoint = Option::get('call', 'call_balancer_url');

		if (empty($endpoint))
		{
			if (in_array($region, Library::REGION_CIS, true))
			{
				$endpoint = self::BALANCER_MAP['ru'];
			}
			elseif (in_array($region, Library::REGION_EU, true))
			{
				$endpoint = self::BALANCER_MAP['eu'];
			}
			else
			{
				$endpoint = self::BALANCER_MAP['us'];
			}
		}
		elseif (!(mb_strpos($endpoint, 'https://') === 0 || mb_strpos($endpoint, 'http://') === 0))
		{
			$endpoint = 'https://' . $endpoint;
		}

		return $endpoint;
	}

	/**
	 * Returns API endpoint for the service.
	 *
	 * @return string
	 */
	public function getServiceUrl(): string
	{
		$region = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion() ?: 'ru';

		return $this->getEndpoint($region);
	}

	/**
	 * @param int $chatId
	 * @param int $tokenVersion
	 * @return Result
	 */
	public function updateTokenVersion(int $chatId, int $tokenVersion): Result
	{
		$data = [
			'portalId' => Settings::getPortalId(),
			'minTokenVersion' => $tokenVersion,
			'chatId' => $chatId
		];

		return $this->performRequest('/v2/update-token-version', $data);
	}

	/**
	 * @param string $method
	 * @param array $data
	 * @return Result
	 */
	protected function performRequest(string $method, array $data = []): Result
	{
		$result = new Result();

		$httpClient = new HttpClient([
			'compress' => true
		]);

		$uri = new Uri($this->getServiceUrl() . $method);

		$jwt = JWT::encode($data, Settings::getPrivateKey());
		$body = new Stream('php://temp', 'r+');
		$body->write(json_encode(['token' => $jwt]));

		$request = new Request(
			Method::POST,
			$uri,
			[
				'bx_call_portal_jwt' => $jwt,
				'User-Agent' => 'Bitrix Call Client '.\Bitrix\Main\Service\MicroService\Client::getPortalType(),
				'Referer' => \Bitrix\Main\Service\MicroService\Client::getServerName(),
			],
			$body
		);

		try
		{
			$response = $httpClient->sendRequest($request);

			$answer = $this->extractAnswer($response);
			if (!$answer->isSuccess())
			{
				$result->addErrors($answer->getErrors());
			}
			else
			{
				$result->setData($answer->getData());
			}
		}
		catch (ClientException $exception)
		{
			$result->addError(new Error(
				Error::BALANCER_ERROR,
				'Exception: '. $exception->getMessage(),
				$data
			));
		}

		return $result;
	}

	/**
	 * @param ResponseInterface $response
	 * @return Result
	 */
	protected function extractAnswer(ResponseInterface $response): Result
	{
		$result = new Result();

		$httpStatus = $response->getStatusCode();
		$answerContent = (string)($response->getBody() ?? '');

		if ($httpStatus == 200)
		{
			if ($answerContent)
			{
				try
				{
					$answer = (array)\json_decode($answerContent, true, 512, \JSON_THROW_ON_ERROR);
					if (isset($answer['result']))
					{
						$result->setData($answer['result']);
					}
					elseif (isset($answer['error']))
					{
						$result->addError(new Error(
							Error::BALANCER_ERROR,
							'Balancer error ['.$answer['error']['code'].']: '.$answer['error']['message']
						));
					}
				}
				catch (\JsonException $exception)
				{
					$result->addError(new Error(
						Error::BALANCER_ERROR,
						'Balancer answer malformed: '. $exception->getMessage()
					));
				}
			}
			else
			{
				$result->addError(new Error(
					Error::BALANCER_ERROR,
					'Unrecognizable or empty balancer answer'
				));
			}
		}
		else
		{
			$result->addError(new Error(
				Error::BALANCER_ERROR,
				'Wrong balancer response '.$response->getStatusCode().' '.$response->getReasonPhrase()
			));
		}

		return $result;
	}
}

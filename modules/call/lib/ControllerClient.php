<?php

namespace Bitrix\Call;

use Bitrix\Main\Result;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Service\MicroService\BaseSender;
use Bitrix\Im\Call\Call;
use Bitrix\Call\Call\PlainCall;
use Bitrix\Call\Call\ConferenceCall;

\Bitrix\Main\Loader::includeModule('im');


class ControllerClient extends BaseSender
{
	private const SERVICE_MAP = [
		'ru' => 'https://videocalls.bitrix.info',
		'eu' => 'https://videocalls-de.bitrix.info',
		'us' => 'https://videocalls-us.bitrix.info',
	];
	private const REGION_RU = ['ru', 'by', 'kz'];
	private const REGION_EU = ['de', 'eu', 'fr', 'it', 'pl', 'tr', 'uk'];

	private array $httpClientParameters = [];

	/**
	 * Returns controller service endpoint url.
	 *
	 * @return string
	 * @param string $region Portal region.
	 */
	public function getEndpoint(string $region): string
	{
		$endpoint = Option::get('im', 'call_server_url');

		if (empty($endpoint))
		{
			if (in_array($region, self::REGION_RU, true))
			{
				$endpoint = self::SERVICE_MAP['ru'];
			}
			elseif (in_array($region, self::REGION_EU, true))
			{
				$endpoint = self::SERVICE_MAP['eu'];
			}
			else
			{
				$endpoint = self::SERVICE_MAP['us'];
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
	protected function getServiceUrl(): string
	{
		$region = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion() ?: 'ru';

		return $this->getEndpoint($region);
	}

	/**
	 * @see \Bitrix\CallController\Controller\InternalApi::createCallAction
	 * @param Call $call
	 * @return Result
	 */
	public function createCall(Call $call): Result
	{
		$action = 'callcontroller.InternalApi.createCall';
		$data = [
			'callType' => 'call',
			'roomType' => $call->getType(),
			'uuid' => $call->getUuid(),
			'initiatorUserId' => $call->getInitiatorId(),
			'callId' => $call->getId(),
			'version' => \Bitrix\Main\ModuleManager::getVersion('call'),
			'usersCount' => count($call->getUsers()),
			'enableRecorder' => (int)$call->isAiAnalyzeEnabled(),
		];

		if ($call instanceof ConferenceCall)
		{
			$data['callType'] = 'conference';
		}

		if ($call instanceof PlainCall)
		{
			$action = 'callcontroller.InternalApi.createPlain';
			$data['callType'] = 'plain';
			$this->httpClientParameters = [
				//'waitResponse' => false,
				'socketTimeout' => 5,
				'streamTimeout' => 5,
			];
		}
		else
		{
			$data = array_merge($data, [
				'secretKey' => $call->getSecretKey(),
				'maxParticipants' => \Bitrix\Im\Call\Call::getMaxCallServerParticipants(),
			]);
			$this->httpClientParameters = [
				'waitResponse' => true,
				'socketTimeout' => 10,
				'streamTimeout' => 15,
			];
		}

		return $this->performRequest($action, $data);
	}

	/**
	 * @see \Bitrix\CallController\Controller\InternalApi::finishCallAction
	 * @see \Bitrix\CallController\Controller\InternalApi::finishPlainAction
	 * @param Call $call
	 * @return Result
	 */
	public function finishCall(Call $call): Result
	{
		$data = [
			'uuid' => $call->getUuid(),
			'actionUserId' => $call->getActionUserId() ?? 0,
		];

		$this->httpClientParameters = [
			'socketTimeout' => 5,
			'streamTimeout' => 5,
		];

		$action = 'callcontroller.InternalApi.finishCall';
		if ($call instanceof ConferenceCall)
		{
			$data['callType'] = 'conference';
		}
		if ($call instanceof PlainCall)
		{
			$action = 'callcontroller.InternalApi.finishPlain';
			$data['callType'] = 'plain';
		}

		return $this->performRequest($action, $data);
	}

	/**
	 * @see \Bitrix\CallController\Controller\InternalApi::startTrackAction
	 * @param Call $call
	 * @return Result
	 */
	public function startTrack(Call $call): Result
	{
		$data = [
			'uuid' => $call->getUuid(),
			'actionUserId' => $call->getActionUserId() ?? 0,
		];

		$this->httpClientParameters = [
			'socketTimeout' => 10,
			'streamTimeout' => 10,
		];

		$action = 'callcontroller.InternalApi.startTrack';
		if ($call instanceof ConferenceCall)
		{
			$data['callType'] = 'conference';
		}
		if ($call instanceof PlainCall)
		{
			$data['callType'] = 'plain';
		}

		return $this->performRequest($action, $data);
	}

	/**
	 * @see \Bitrix\CallController\Controller\InternalApi::stopTrackAction
	 * @param Call $call
	 * @return Result
	 */
	public function stopTrack(Call $call): Result
	{
		$data = [
			'uuid' => $call->getUuid(),
			'actionUserId' => $call->getActionUserId() ?? 0,
		];

		$this->httpClientParameters = [
			'socketTimeout' => 10,
			'streamTimeout' => 10,
		];

		$action = 'callcontroller.InternalApi.stopTrack';
		if ($call instanceof ConferenceCall)
		{
			$data['callType'] = 'conference';
		}
		if ($call instanceof PlainCall)
		{
			$data['callType'] = 'plain';
		}

		return $this->performRequest($action, $data);
	}

	/**
	 * @see \Bitrix\CallController\Controller\InternalApi::dropTrackAction
	 * @param Track $track
	 * @return Result
	 */
	public function dropTrack(Track $track): Result
	{
		$data = [
			'uuid' => $track->fillCall()->getUuid(),
			'trackId' => $track->getExternalTrackId(),
		];

		$this->httpClientParameters = [
			'socketTimeout' => 5,
			'streamTimeout' => 5,
		];

		return $this->performRequest('callcontroller.InternalApi.dropTrack', $data);
	}

	public function getHttpClientParameters(): array
	{
		return array_merge(
			parent::getHttpClientParameters(),
			['headers' => [
				'User-Agent' => 'Bitrix Call Client '.\Bitrix\Main\Service\MicroService\Client::getPortalType(),
				'Referer' => \Bitrix\Main\Service\MicroService\Client::getServerName(),
			]],
			$this->httpClientParameters
		);
	}
}
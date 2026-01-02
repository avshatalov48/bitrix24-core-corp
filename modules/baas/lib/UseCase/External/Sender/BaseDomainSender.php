<?php

namespace Bitrix\Baas\UseCase\External\Sender;

use Bitrix\Main;
use Bitrix\Baas;
use Bitrix\Baas\UseCase\External\Exception;
use Bitrix\Main\Web\HttpClient;

class BaseDomainSender extends Main\Service\MicroService\BaseSender
{
	private string $url;

	public function __construct(
		protected Baas\UseCase\External\Entity\Server $server,
		protected Baas\UseCase\External\Entity\Client $client,
	)
	{
		parent::__construct();
		$this->url = $this->server->getUrl();
	}

	public function performRequest($action, array $parameters = []): Response\SenderResult
	{
		/** @var Response\SenderResult $result */
		$result = parent::performRequest($this->formatActionName($action), $parameters);

		if (!$result->isSuccess())
		{
			throw Exception\BaasControllerException\Factory::createFromErrorCollection(
				$result->getErrorCollection(),
			);
		}

		return $result;
	}

	protected function buildResult(HttpClient $httpClient, bool $requestResult): Response\SenderResult
	{
		$baseResult = parent::buildResult($httpClient, $requestResult);

		(new Main\Event(
			'baas',
			'onServerInfoReceived',
			[
				'httpClient' => $httpClient,
				'result' => $baseResult,
			])
		)->send();

		return
			(new Response\SenderResult())
				->addErrors(
					$baseResult->getErrors(),
				)
				->setData($baseResult->getData())
				->setClient($httpClient)
		;
	}

	protected function formatActionName(string $action): string
	{
		return "baascontroller.Domain.$action";
	}

	protected function getServiceUrl(): string
	{
		return $this->url;
	}
}

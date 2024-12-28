<?php

declare(strict_types=1);

namespace Bitrix\Disk\Document\OnlyOffice\Clients\CommandService;

use Bitrix\Disk\Document\OnlyOffice\Clients\CommandService\Commands\BaseCommand;
use Bitrix\Disk\Document\OnlyOffice\Clients\CommandService\Commands\DropCommand;
use Bitrix\Disk\Document\OnlyOffice\Clients\CommandService\Commands\MetaCommand;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\JWT;

class DirectAccessCommandServiceClient implements CommandServiceClientInterface
{
	private readonly HttpClient $http;
	private readonly string $url;
	private readonly string $secretKey;

	public function __construct(string $serverUrl, string $secretKey)
	{
		$serverUrl = rtrim($serverUrl, '/');
		$this->url = $serverUrl . '/coauthoring/CommandService.ashx';

		$this->buildHttpClient();
		$this->secretKey = $secretKey;
	}

	public function rename(string $documentKey, string $newName): Result
	{
		return $this->performRequest(new MetaCommand($documentKey, [
			'title' => $newName,
		]));
	}

	public function drop(string $documentKey, array $userIds): Result
	{
		return $this->performRequest(new DropCommand($documentKey, $userIds));
	}

	private function performRequest(BaseCommand $commandData): Result
	{
		$result = new Result();

		$this->setAuthHeader($commandData);

		try
		{
			$postFields = Json::encode($commandData);
		}
		catch (ArgumentException $e)
		{
			return $result->addError(new Error($e->getMessage()));
		}

		if ($this->http->post($this->url, $postFields) === false)
		{
			return $result->addError(new Error('Server is not available.'));
		}

		$status = $this->http->getStatus();
		if ($status !== 200)
		{
			return $result->addError(new Error('Server is not available. Status ' . $status));
		}

		try
		{
			$response = Json::decode($this->http->getResult());
		}
		catch (ArgumentException $e)
		{
			return $result->addError(new Error($e->getMessage()));
		}

		if (isset($response['error']) && $response['error'] !== 0)
		{
			return $result->addError(new Error("Server sent error code {{$response['error']}}"));
		}

		return $result;
	}

	private function setAuthHeader(BaseCommand $commandData): void
	{
		$this->http->setHeader('Authorization', 'Bearer ' . JWT::encode($commandData, $this->secretKey));
	}

	private function buildHttpClient(): void
	{
		$this->http = new HttpClient([
			'socketTimeout' => 5,
			'streamTimeout' => 10,
			'version' => HttpClient::HTTP_1_1,
		]);

		$this->http->setHeader('Content-Type', 'application/json');
	}
}
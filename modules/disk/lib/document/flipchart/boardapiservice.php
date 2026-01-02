<?php

declare(strict_types=1);

namespace Bitrix\Disk\Document\Flipchart;

use Bitrix\Disk\Document\FileDownloader;
use Bitrix\Disk\File;
use Bitrix\Disk\TypeFile;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\Result;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Web\Http\Method;
use Bitrix\Main\Web\HttpClient;
use \Bitrix\Disk\Document\Models\DocumentSession;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\JWT;

class BoardApiService
{
	private string $baseUrl;

	public function __construct(?string $baseUrl = null)
	{
		if (is_null($baseUrl))
		{
			if (Configuration::isUsingDocumentProxy())
			{
				$jwt = new JwtService();
				$tokenFromProxy = $jwt->generateToken();
				if (!$tokenFromProxy)
				{
					$tokenFromProxy = $jwt->generateToken();
				}
				$baseUrl = $jwt->getApiUrlFromProxy();
				if (!$baseUrl)
				{
					throw new \Exception('Proxy inavailable');
				}
			}
			else
			{
				$baseUrl = Configuration::getApiHost();
			}
		}
		$this->baseUrl = $baseUrl;
	}

	public function downloadBoard(string $url, string $method = Method::GET, $entityBody = null): Result
	{
		$url = $this->baseUrl . $url;
		$token = (new JwtService())->generateToken();

		$httpClient = new HttpClient();
		$httpClient->disableSslVerification();
		$httpClient->setHeader(
			Configuration::getClientTokenHeaderLookup(),
			$token,
		);

		$downloader = new FileDownloader(
			$url,
			$httpClient,
		);

		return $downloader->download($method, $entityBody);
	}

	public function downloadBlank(): Result
	{
		return $this->downloadBoard('/api/v1/file/new', Method::POST);
	}

	public function kickUsers(string $documentId, array $userIds): bool|string
	{
		$url = $this->baseUrl . '/api/v1/flip/kick';
		$token = (new JwtService())->generateToken(
			false,
			[
				'document_id' => $documentId
			]
		);

		$httpClient = new HttpClient();
		$httpClient->disableSslVerification();
		$httpClient->setHeader('Content-Type', 'application/json');
		$httpClient->setHeader(
			Configuration::getClientTokenHeaderLookup(),
			$token,
		);

		$userIds = array_map(static fn ($userId) => (string)$userId, $userIds);

		return $httpClient->post($url, Json::encode([
			'user_id' => $userIds,
		]));
	}

	public function getActiveUsersByDocumentId(string $documentId): ?array
	{
		$url = $this->baseUrl . '/api/v1/flip/users';
		$token = (new JwtService())->generateToken(
			false,
			[
				'document_id' => $documentId
			]
		);

		$httpClient = new HttpClient();
		$httpClient->disableSslVerification();
		$httpClient->setHeader('Content-Type', 'application/json');
		$httpClient->setHeader(
			Configuration::getClientTokenHeaderLookup(),
			$token,
		);

		$data = $httpClient->get($url);

		if (!$data || !Json::validate($data))
		{
			return null;
		}

		return (array) Json::decode($data);
	}
}
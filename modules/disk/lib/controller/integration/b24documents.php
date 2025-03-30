<?php

namespace Bitrix\Disk\Controller\Integration;

use Bitrix\Disk;
use Bitrix\Disk\Document;
use Bitrix\Disk\Internals\Engine;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\UserConfiguration;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Security\Cipher;
use Bitrix\Main\Security\SecurityException;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;

final class B24Documents extends Engine\Controller
{
	public function configureActions()
	{
		return [
			'verifyDomain' => [
				'prefilters' => [],
				'+postfilters' => [
					function () {
						(new Document\OnlyOffice\Configuration())->resetTempSecretForDomainVerification();
					}
				],
			],
		];
	}

	public function unregisterCloudClientAction(string $languageId): void
	{
		if (!$this->getCurrentUser()->isAdmin())
		{
			$this->addError(new Error('Only administrator can unregister portal.'));

			return;
		}

		$configuration = new Document\OnlyOffice\Configuration();
		$cloudRegistrationData = $configuration->getCloudRegistrationData();
		if (!$cloudRegistrationData)
		{
			return;
		}

		$serviceUrl = $cloudRegistrationData['serverHost'];
		$cloudRegistration = (new Document\OnlyOffice\Cloud\Registration($serviceUrl))
			->setLanguageId($languageId)
		;

		$result = $cloudRegistration->unregisterPortal();
		if ($result->isSuccess())
		{
			$configuration->resetCloudRegistration();
			UserConfiguration::resetDocumentServiceForAllUsers();

			Disk\Configuration::setDefaultViewerService(Document\BitrixHandler::getCode());
			Document\Models\DocumentSessionTable::clearTable();
		}
		else
		{
			$this->addErrors($result->getErrors());
		}
	}

	public function registerCloudClientAction(string $serviceUrl, string $languageId): void
	{
		if (!$this->getCurrentUser()->isAdmin())
		{
			$this->addError(new Error('Only administrator can register portal and connect cloud server.'));

			return;
		}

		$configuration = new Document\OnlyOffice\Configuration();
		if ($configuration->getCloudRegistrationData())
		{
			return;
		}

		$cloudRegistration = (new Document\OnlyOffice\Cloud\Registration($serviceUrl))
			->setLanguageId($languageId)
		;

		$result = $cloudRegistration->registerPortal();
		if ($result->isSuccess() && isset($result->getData()['client']))
		{
			$configuration->storeCloudRegistration($result->getData()['client']);

			Option::set('disk', 'documents_enabled', 'Y');
			Option::set('disk', 'disk_onlyoffice_server', $result->getData()['documentServer']['host']);
			Disk\Configuration::setDefaultViewerService(Document\OnlyOffice\OnlyOfficeHandler::getCode());
		}
		else
		{
			$this->addErrors($result->getErrors());
		}
	}

	public function verifyDomainAction(): ?array
	{
		$configuration = new Document\OnlyOffice\Configuration();
		$tempSecretForDomainVerification = $configuration->getTempSecretForDomainVerification();
		if (!$tempSecretForDomainVerification)
		{
			$this->addError(new Error('Empty secret.'));

			return null;
		}

		try
		{
			$cipher = new Cipher();
			$message = base64_encode($cipher->encrypt('42', $tempSecretForDomainVerification));
		}
		catch (SecurityException $securityException)
		{
			$this->addError(new Error("Cipher doesn't happy."));

			return null;
		}

		return [
			'message' => $message,
		];
	}

	public function listAllowedServersAction(): array
	{
		$primarySettings = Configuration::getInstance()->get('b24documents');
		if (!empty($primarySettings['proxyServers']))
		{
			return [
				'servers' => $primarySettings['proxyServers'],
			];
		}

		$configuration = new Document\OnlyOffice\Configuration();
		$serverListUrl = $configuration->getB24DocumentsServerListEndpoint();
		if (!$serverListUrl)
		{
			return [];
		}

		$http = new HttpClient([
			'socketTimeout' => 5,
			'streamTimeout' => 5,
			'version' => HttpClient::HTTP_1_1,
		]);

		if ($http->get($serverListUrl) === false)
		{
			$this->addError(new Error('Server is not available.'));

			return [];
		}
		if ($http->getStatus() !== 200)
		{
			$this->addError(new Error('Server is not available. Status ' . $http->getStatus()));

			return [];
		}

		$response = Json::decode($http->getResult());
		if (!$response)
		{
			$this->addError(new Error('Could not decode response.'));

			return [];
		}

		if (empty($response['servers']))
		{
			$this->addError(new Error('Empty server list.'));

			return [];
		}

		$servers = [];
		foreach ($response['servers'] as $server)
		{
			$servers[] = [
				'proxy' => $server['proxy'],
				'region' => $server['region'] ?? null,
			];
		}

		return [
			'servers' => $servers,
		];
	}
}

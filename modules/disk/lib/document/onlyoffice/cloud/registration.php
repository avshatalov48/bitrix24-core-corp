<?php

namespace Bitrix\Disk\Document\OnlyOffice\Cloud;

use Bitrix\Disk\Document\OnlyOffice\Configuration;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Web\HttpClient;

final class Registration extends BaseSender
{
	/** @var string */
	private $languageId = 'en';

	public function setLanguageId(string $languageId): self
	{
		if ($languageId)
		{
			$this->languageId = $languageId;
		}

		return $this;
	}

	public function unregisterPortal(): Result
	{
		$cloudRegistrationData = (new Configuration())->getCloudRegistrationData();
		if (!$cloudRegistrationData)
		{
			$result = new Result();
			$result->addError(new Error('There is empty cloud registration data.'));

			return $result;
		}

		$data = [
			'clientId' => $cloudRegistrationData['clientId'],
			'languageId' => $this->languageId,
		];

		/** @see \Bitrix\DocumentProxy\Controller\Registration::unregisterClientAction */
		return $this->performRequest('documentproxy.Registration.unregisterClient', $data);
	}

	public function registerPortal(): Result
	{
		$hostUrl = UrlManager::getInstance()->getHostUrl();
		/** @see \Bitrix\DocumentProxy\Controller\Registration::registerClientAction */
		$data = [
			'domain' => $hostUrl,
			'languageId' => $this->languageId,
		];

		return $this->performRequest('documentproxy.Registration.registerClient', $data);
	}

	protected function buildResult(HttpClient $httpClient, bool $requestResult): Result
	{
		if ($requestResult)
		{
			$tempSecret = $httpClient->getHeaders()->get('X-Temp-Secret');
			if ($tempSecret)
			{
				(new Configuration())->storeTempSecretForDomainVerification($tempSecret);
			}
		}

		return $this->createAnswerForJsonResponse(
			$requestResult,
			$httpClient->getResult(),
			$httpClient->getError(),
			$httpClient->getStatus()
		);
	}
}
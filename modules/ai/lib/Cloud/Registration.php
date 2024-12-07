<?php
declare(strict_types=1);

namespace Bitrix\AI\Cloud;

use Bitrix\AI\Cloud\Dto\RegistrationDto;
use Bitrix\AI\Cloud\HttpClient\ResponseBuilder;
use Bitrix\AI\Cloud\Result\RegistrationResult;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;

final class Registration extends BaseSender
{
	private string $languageId = 'en';

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
			'clientId' => $cloudRegistrationData->clientId,
			'languageId' => $this->languageId,
		];

		/** @see \Bitrix\AiProxy\Controller\Registration::unregisterClientAction */
		return $this->performRequest('aiproxy.Registration.unregisterClient', $data);
	}

	public function registerPortal(): Result|RegistrationResult
	{
		$hostUrl = UrlManager::getInstance()->getHostUrl();
		/** @see \Bitrix\AiProxy\Controller\Registration::registerClientAction */
		$data = [
			'domain' => $hostUrl,
			'languageId' => $this->languageId,
		];

		$result = $this->performRequest('aiproxy.Registration.registerClient', $data);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$registrationData = $result->getData()['client'];
		$registrationDto = new RegistrationDto(
			$registrationData['clientId'],
			$registrationData['secretKey'],
			$registrationData['serverHost'],
		);

		return new RegistrationResult($registrationDto);
	}

	protected function buildHttpClient(): HttpClient
	{
		$tokenRetrieved = false;

		$responseBuilder = new ResponseBuilder();
		// work with SSE
		$responseBuilder->setReadPortionCallback(function (string $content) use (&$tokenRetrieved) {
			if (empty($content) || $tokenRetrieved)
			{
				return null;
			}

			$strings = [];
			if (
				str_starts_with($content, 'id:')
				|| str_starts_with($content, 'event:')
				|| str_starts_with($content, 'data:')
				|| str_starts_with($content, 'retry:')
			)
			{
				$strings = explode("\n", $content);
			}
			elseif (str_starts_with($content, 'data:'))
			{
				$strings = [$content];
			}

			foreach ($strings as $string)
			{
				if (str_starts_with($string, 'data:'))
				{
					$data = substr($string, 5);
					$data = trim($data);
					$eventData = Json::decode($data);

					if (!\is_array($eventData))
					{
						return null;
					}

					if (isset($eventData['token']) && \is_string($eventData['token']))
					{
						(new Configuration())->storeTempSecretForDomainVerification($eventData['token']);
						$tokenRetrieved = true;

						break;
					}
				}
			}
		});

		$httpClient = new HttpClient($this->getHttpClientParameters());
		$httpClient->setVersion(HttpClient::HTTP_1_0);
		$httpClient->setResponseBuilder($responseBuilder);

		return $httpClient;
	}

	protected function createAnswerForJsonResponse($queryResult, $response, $errors, $status): Result
	{
		if (\is_string($response))
		{
			$parts = explode("\n", $response);
			$parts = array_reverse($parts);

			foreach ($parts as $part)
			{
				if (empty($part))
				{
					continue;
				}

				if (str_starts_with($part, 'data:'))
				{
					$data = substr($part, 5);
					$data = trim($data);
					$response = $data;

					break;
				}
			}
		}

		return parent::createAnswerForJsonResponse($queryResult, $response, $errors, $status);
	}
}
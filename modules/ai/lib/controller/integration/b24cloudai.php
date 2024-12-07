<?php
declare(strict_types=1);

namespace Bitrix\AI\Controller\Integration;

use Bitrix\AI\Cloud;
use Bitrix\AI\Cloud\Agent;
use Bitrix\AI\Engine;
use Bitrix\AI\QueueJob;
use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\JsonPayload;
use Bitrix\Main\Security\Cipher;
use Bitrix\Main\Security\SecurityException;
use JetBrains\PhpStorm\ArrayShape;

/**
 * Class B24CloudAi
 */
class B24CloudAi extends Controller
{
	public function configureActions(): array
	{
		$cloudPreFilters = $this->getDefaultCloudPreFilters();

		return [
			'verifyDomain' => [
				'prefilters' => [],
				'+postfilters' => [
					function () {
						(new Cloud\Configuration())->resetTempSecretForDomainVerification();
					}
				]
			],
			'testEcho' => [
				'prefilters' => $cloudPreFilters,
			],
			'callbackSuccess' => [
				'prefilters' => $cloudPreFilters,
			],
			'callbackError' => [
				'prefilters' => $cloudPreFilters,
			],
		];
	}

	protected function getDefaultPreFilters(): array
	{
		return [
			new ActionFilter\Authentication(),
		];
	}

	private function getDefaultCloudPreFilters(): array
	{
		$filters = [
			new Engine\Cloud\ActionFilter\CloudProxyEnabled(),
		];

		$configuration = new Cloud\Configuration();
		if ($configuration->hasCloudRegistration() === false)
		{
			return $filters;
		}

		$cloudRegistrationData = $configuration->getCloudRegistrationData();
		if ($cloudRegistrationData)
		{
			$filters[] = new Engine\Cloud\ActionFilter\Authorization($cloudRegistrationData->secretKey);
		}

		return $filters;
	}

	/**
	 * Test action for Queue.
	 * @param JsonPayload $payload Payload.
	 * @return array
	 */
	#[ArrayShape(['payload' => 'array', 'time' => 'int'])]
	public function testEchoAction(JsonPayload $payload): array
	{
		return [
			'payload' => $payload->getData(),
			'time' => time(),
		];
	}

	/**
	 * Accepts external result for Queue Job.
	 *
	 * @param string $hash Job hash.
	 * @param JsonPayload $result Queue result.
	 * @return bool
	 */
	public function callbackSuccessAction(string $hash, JsonPayload $result): bool
	{
		$queueJob = QueueJob::createFromHash($hash);
		if ($queueJob)
		{
			$queueJob->execute($result->getData());

			return true;
		}

		return false;
	}

	/**
	 * Accepts external error for Queue Job.
	 *
	 * @param string $hash Job hash.
	 * @param JsonPayload $result Queue error.
	 * @return bool
	 */
	public function callbackErrorAction(string $hash, JsonPayload $result): bool
	{
		$queueJob = QueueJob::createFromHash($hash);
		if ($queueJob)
		{
			$queueJob->fail($result->getData());

			return true;
		}

		return false;
	}

	/**
	 * Verifies domain for cloud to check if it's possible to connect to it.
	 * @return array|null
	 */
	public function verifyDomainAction(): ?array
	{
		$configuration = new Cloud\Configuration();
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
		catch (SecurityException)
		{
			$this->addError(new Error("Cipher doesn't happy."));

			return null;
		}

		return [
			'message' => $message,
		];
	}

	/**
	 * Returns list of allowed proxy servers.
	 * @return array
	 * @throws ArgumentException
	 */
	public function listAllowedServersAction(): array
	{
		try
		{
			$serverList = new Cloud\ServerList();
			$servers = $serverList->getAllowedServers();
		}
		catch (\RuntimeException $e)
		{
			$this->addError(new Error($e->getMessage()));

			return [];
		}

		return [
			'servers' => $servers,
		];
	}

	/**
	 * Registers portal in cloud.
	 * @param string $serviceUrl
	 * @param string $languageId
	 * @return void
	 */
	public function registerAction(string $serviceUrl, string $languageId): void
	{
		if (!$this->getCurrentUser() || !$this->getCurrentUser()->isAdmin())
		{
			$this->addError(new Error('Only administrator can register portal and connect cloud server.'));

			return;
		}

		$scenarioRegistration = new Cloud\Scenario\Registration($languageId);

		$result = $scenarioRegistration->register($serviceUrl);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}

		if ($result->isSuccess())
		{
			Application::getInstance()->addBackgroundJob(fn () => Agent\PropertiesSync::retrieveModels());
		}
	}

	public function unregisterAction(string $languageId): void
	{
		if (!$this->getCurrentUser() || !$this->getCurrentUser()->isAdmin())
		{
			$this->addError(new Error('Only administrator can unregister portal.'));

			return;
		}

		$scenarioRegistration = new Cloud\Scenario\Registration($languageId);

		$result = $scenarioRegistration->unregister();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}
	}
}

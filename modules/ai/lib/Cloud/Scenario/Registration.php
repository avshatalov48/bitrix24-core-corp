<?php
declare(strict_types=1);

namespace Bitrix\AI\Cloud\Scenario;

use Bitrix\AI\Cloud;
use Bitrix\Main\Error;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use CAdminNotify;

/**
 * Class Registration
 * Manages registration of the portal in cloud aiproxy service.
 */
final class Registration
{
	/** @see \Bitrix\AiProxy\Controller\BaseReceiver::ERROR_CLIENT_NOT_FOUND */
	private const ERROR_CLIENT_NOT_FOUND = 'client_not_found';
	public const NOTIFICATION_TAG = 'ai_cloud_failed_registration';

	public function __construct(
		private readonly string $languageId,
	)
	{
	}

	/**
	 * Register portal in cloud aiproxy service.
	 * @param string $serviceUrl Service URL.
	 * @return Result
	 */
	public function register(string $serviceUrl): Result
	{
		$result = new Result();

		$cloudRegistration = new Cloud\Registration($serviceUrl);
		$cloudRegistration->setLanguageId($this->languageId);

		$registrationResult = $cloudRegistration->registerPortal();
		if (!$registrationResult->isSuccess())
		{
			$result->addErrors($registrationResult->getErrors());

			return $result;
		}

		if (!($registrationResult instanceof Cloud\Result\RegistrationResult))
		{
			$result->addError(new Error('Unexpected result. Should be RegistrationResult.'));

			return $result;
		}

		$configuration = new Cloud\Configuration();
		$configuration->storeCloudRegistration($registrationResult->getRegistrationData());
		CAdminNotify::DeleteByTag(self::NOTIFICATION_TAG);

		return $result;
	}

	/**
	 * Unregister portal in cloud aiproxy service.
	 * @return Result
	 */
	public function unregister(): Result
	{
		$result = new Result();

		$configuration = new Cloud\Configuration();
		$cloudRegistrationData = $configuration->getCloudRegistrationData();
		if (!$cloudRegistrationData)
		{
			$result->addError(new Error('No cloud registration data found.'));

			return $result;
		}

		$cloudRegistration = new Cloud\Registration($cloudRegistrationData->serverHost);
		$cloudRegistration->setLanguageId($this->languageId);

		$unregisterPortal = $cloudRegistration->unregisterPortal();
		if (
			!$unregisterPortal->isSuccess()
			&& !$unregisterPortal->getErrorCollection()->getErrorByCode(self::ERROR_CLIENT_NOT_FOUND)
		)
		{
			$result->addErrors($unregisterPortal->getErrors());

			return $result;
		}

		$configuration->resetCloudRegistration();
		CAdminNotify::DeleteByTag(self::NOTIFICATION_TAG);

		return $result;
	}

	/**
	 * Tries to register portal in cloud aiproxy service. Uses most suitable server.
	 * If registration fails, adds a notification to admin panel.
	 * @return Result
	 * @throws ArgumentException
	 */
	public function tryAutoRegister(): Result
    {
		$result = new Result();

		$serverList = new Cloud\ServerList();
		$mostSuitableServer = $serverList->getMostSuitableServer();

		if ($mostSuitableServer)
		{
			$result = $this->register($mostSuitableServer);
			if (!$result->isSuccess())
			{
				\CAdminNotify::add([
					'MESSAGE' => Loc::getMessage('AI_CLOUD_SCENARIO_REGISTRATION_ADMIN_NOTIFY_WARN'),
					'TAG' => self::NOTIFICATION_TAG,
					'MODULE_ID' => 'ai',
					'ENABLE_CLOSE' => 'N',
					'NOTIFY_TYPE' => 'E',
				]);
			}
		}

        return $result;
    }
}
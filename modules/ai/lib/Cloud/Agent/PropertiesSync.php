<?php
declare(strict_types=1);

namespace Bitrix\AI\Cloud\Agent;

use Bitrix\AI\Cloud;
use Bitrix\AI\Cloud\EngineProviders;
use Bitrix\AI\Config;
use Bitrix\AI\Engine\Cloud\EngineProperty\Model;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Web\Json;
use function is_array;

/**
 * Class PropertiesSync
 */
final class PropertiesSync
{
	/**
	 * Retrieve models which are available for the current bitrix24.
	 * @return void
	 */
	public static function retrieveModels(): void
	{
		$cloudConfiguration = new Cloud\Configuration();

		$registrationDto = $cloudConfiguration->getCloudRegistrationData();
		if (!$registrationDto)
		{
			return;
		}

		$result = (new EngineProviders($registrationDto->serverHost))->listModels();
		if (!$result->isSuccess())
		{
			return;
		}

		$models = $result->getData();
		if (!is_array($models))
		{
			return;
		}

		try
		{
			Config::setOptionsValue(Model::OPTION_NAME, Json::encode($models));
		}
		catch (ArgumentException)
		{
		}
	}

	/**
	 * Retrieve models which are available for the current bitrix24.
	 * This method is used in the agent.
	 * @return string
	 */
	public static function retrieveModelsAgent(): string
	{
		self::retrieveModels();

		return self::class . '::retrieveModelsAgent();';
	}
}
<?php
declare(strict_types=1);

namespace Bitrix\AI\Cloud;

use Bitrix\Main\Error;
use Bitrix\Main\Result;

/**
 * Class EngineProviders
 */
final class EngineProviders extends BaseSender
{
	public function listModels(): Result
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
		];

		/** @see \Bitrix\AiProxy\Controller\EngineProvider::listModelsAction */
		return $this->performRequest('aiproxy.EngineProvider.listModels', $data);
	}
}
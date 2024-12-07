<?php
declare(strict_types=1);

namespace Bitrix\AI\Cloud;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

/**
 * Class SendQuery
 * Provides methods for sending queries to AI service.
 */
final class SendQuery extends BaseSender
{
	/**
	 * Send query to AI service.
	 * @param array $body Query body.
	 * @return Result
	 * @throws ArgumentException
	 */
	public function queue(array $body): Result
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
			'queryBody' => $body,
		];

		/** @see \Bitrix\AiProxy\Controller\Query::queueAction */
		return $this->performRequest('aiproxy.Query.queue', $data);
	}
}
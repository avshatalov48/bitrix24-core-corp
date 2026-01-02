<?php
declare(strict_types=1);

namespace Bitrix\AI\Cloud;

use Bitrix\AI\Cloud\Dto\RegistrationDto;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Result;

final class LimitInspector extends BaseSender
{
	/**
	 * @param array $body
	 * @param RegistrationDto $registrationDto
	 * @return Result
	 * @throws ArgumentException
	 */
	public function isAllowedQuery(array $body, RegistrationDto $registrationDto): Result
	{
		$data = [
			'clientId' => $registrationDto->clientId,
			'queryBody' => $body,
		];

		/** @see \Bitrix\AiProxy\Controller\Limit::isAllowedQueryAction() */
		return $this->performRequest('aiproxy.Limit.isAllowedQuery', $data);
	}
}

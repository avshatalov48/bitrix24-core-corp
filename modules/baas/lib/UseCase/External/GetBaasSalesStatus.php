<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External;

class GetBaasSalesStatus extends BaseClientAction
{
	protected function run(): Response\GetBaasSalesStatusResult
	{
		$result = $this
			->getSender()
			->performRequest('getBaasStatus', ['languageId' => LANGUAGE_ID])
		;

		return new Response\GetBaasSalesStatusResult(
			200,
			$result->getData()['code'] ?? 'ERROR',
			$result->getData()['description'] ?? 'The Baas Server is not response.',
		);
	}
}

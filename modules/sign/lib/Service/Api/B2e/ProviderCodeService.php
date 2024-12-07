<?php

namespace Bitrix\Sign\Service\Api\B2e;


use Bitrix\Sign\Service;

final class ProviderCodeService
{
	public function __construct(
		private readonly Service\ApiService $api,
	) {}

	public function loadProviderCode(string $companyUid): ?string
	{
		$apiResult = $this->api->get('v1/b2e.company.provider.get', [
			'companyUid' => $companyUid
		]);

		if (!$apiResult->isSuccess())
		{
			return null;
		}

		$providerCode = $apiResult->getData()['code'] ?? null;
		if ($providerCode === null)
		{
			return null;
		}
		if (!is_string($providerCode))
		{
			return null;
		}

		return $providerCode;
	}
}
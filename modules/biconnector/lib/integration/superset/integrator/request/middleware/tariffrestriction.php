<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator\Request\Middleware;

use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorRequest;
use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorResponse;
use Bitrix\Bitrix24\Feature;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;


class TariffRestriction extends Base
{
	private const ID = 'STATUS_ARBITER';

	public static function getMiddlewareId(): string
	{
		return self::ID;
	}

	public function beforeRequest(IntegratorRequest $request): ?IntegratorResponse
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return null;
		}

		if (Feature::isFeatureEnabled('bi_constructor'))
		{
			return null;
		}

		return new IntegratorResponse(
			IntegratorResponse::STATUS_INNER_ERROR,
			null,
			[new Error('Request blocked by tariff restriction: bi_constructor feature are disabled', IntegratorResponse::STATUS_INNER_ERROR)]
		);
	}
}

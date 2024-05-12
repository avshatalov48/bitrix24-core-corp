<?php

namespace Bitrix\BIConnector\Controller;

use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\BIConnector\Superset\ActionFilter\ProxyAuth;
use Bitrix\BIConnector\Superset\Logger\SupersetInitializerLogger;
use Bitrix\Main\Context;
use Bitrix\Main\Error;
use Bitrix\Main\Engine\Controller;

class Callback extends Controller
{
	public function getDefaultPreFilters(): array
	{
		return [
			new ProxyAuth(),
		];
	}

	public function enableSupersetAction(): void
	{
		if (SupersetInitializer::getSupersetStatus() === SupersetInitializer::SUPERSET_STATUS_FROZEN)
		{
			SupersetInitializer::setSupersetUnfreezed();

			return;
		}

		$context = Context::getCurrent();

		$responseBody = $context->getRequest()->getJsonList();
		$status = $responseBody->get('status');
		if (isset($status) && $status === 'error')
		{
			$errorMsg = $responseBody->get('error') ?? 'Unknown server error';
			$error = new Error($errorMsg);
			SupersetInitializer::onUnsuccessfulSupersetStartup($error);

			return;
		}

		if (SupersetInitializer::getSupersetStatus() === SupersetInitializer::SUPERSET_STATUS_LOAD)
		{
			SupersetInitializer::enableSuperset($responseBody->get('superset_address') ?? '');
		}
	}

	public function freezeAction(): void
	{
		SupersetInitializerLogger::logInfo('Portal got freeze action', ['current_status' => SupersetInitializer::getSupersetStatus()]);
	}
}
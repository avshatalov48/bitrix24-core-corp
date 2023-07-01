<?php

namespace Bitrix\ImBot\Sender;

use Bitrix\Main\Application;
use Bitrix\Main\Service\MicroService\BaseSender;

abstract class Base extends BaseSender
{
	protected function getServiceUrl(): string
	{
		$region = Application::getInstance()->getLicense()->getRegion() ?: 'ru';

		$serviceEndpoint = \Bitrix\ImBot\Http::getServiceEndpoint($region);

		return str_replace('/json/', '', $serviceEndpoint);
	}
}
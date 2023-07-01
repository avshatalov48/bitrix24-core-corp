<?php
namespace Bitrix\BIConnector\Services;

use Bitrix\BIConnector\Service;

class YandexDataLens extends MicrosoftPowerBI
{
	protected static $serviceId = 'datalens';

	/**
	 * @inheritDoc
	 */
	public static function validateDashboardUrl(\Bitrix\Main\Event $event)
	{
		$url = $event->getParameters()[0];
		$uri = new \Bitrix\Main\Web\Uri($url);
		$isUrlOk =
			($uri->getScheme() === 'https')
			&& ($uri->getHost() === 'datalens.yandex.ru')
		;
		$result = new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, ($isUrlOk ? 3 : 0));
		return $result;
	}
}

<?php
namespace Bitrix\BIConnector\Services;

use Bitrix\BIConnector\Service;

class MicrosoftPowerBI extends Service
{
	protected static $serviceId = 'pbi';
	public static $dateFormats = [
		'datetime_format' => '%Y-%m-%d %H:%i:%s',
		'datetime_format_php' => 'Y-m-d H:i:s',
		'date_format' => '%Y-%m-%d',
		'date_format_php' => 'Y-m-d',
	];

	/**
	 * @inheritDoc
	 */
	public static function validateDashboardUrl(\Bitrix\Main\Event $event)
	{
		$url = $event->getParameters()[0];
		$uri = new \Bitrix\Main\Web\Uri($url);
		$isUrlOk =
			$uri->getScheme() === 'https'
			&& $uri->getHost() === 'app.powerbi.com'
			&& (
				$uri->getPath() === '/view'
				|| $uri->getPath() === '/reportEmbed'
			)
		;
		$result = new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, ($isUrlOk ? 1 : 0));
		return $result;
	}
}

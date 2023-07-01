<?php
namespace Bitrix\BIConnector\Services;

use Bitrix\BIConnector\Service;

class GoogleDataStudio extends Service
{
	protected static $serviceId = 'gds';
	public static $dateFormats = [
		'datetime_format' => '%Y%m%d%H%i%s',
		'datetime_format_php' => 'YmdHis',
		'date_format' => '%Y%m%d',
		'date_format_php' => 'Ymd',
	];

	public const URL_CREATE = 'https://datastudio.google.com/datasources/create';
	public const OPTION_DEPLOYMENT_ID = 'gds_deployment_id';

	/**
	 * @inheritDoc
	 */
	public static function validateDashboardUrl(\Bitrix\Main\Event $event)
	{
		$url = $event->getParameters()[0];
		$uri = new \Bitrix\Main\Web\Uri($url);
		$isUrlOk =
			$uri->getScheme() === 'https'
			&& (
				$uri->getHost() === 'datastudio.google.com'
				|| $uri->getHost() === 'lookerstudio.google.com'
			)
			&& strpos($uri->getPath(), '/embed/reporting/') === 0
		;
		$result = new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, ($isUrlOk ? 1 : 0));
		return $result;
	}
}

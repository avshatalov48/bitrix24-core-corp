<?php
namespace Bitrix\BIConnector\Services;

class ApacheSuperset extends MicrosoftPowerBI
{
	protected static $serviceId = 'superset';
	private static string $consumer = 'bi-ctr';

	/**
	 * Event OnBIConnectorCreateServiceInstance habler.
	 *
	 * @param \Bitrix\Main\Event $event Event parameters.
	 *
	 * @return \Bitrix\Main\EventResult
	 */
	public static function createServiceInstance(\Bitrix\Main\Event $event)
	{
		$service = null;

		[$serviceId, $manager] = $event->getParameters();
		if ($serviceId === self::$consumer)
		{
			$service = new static($manager);
		}

		return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, $service);
	}
}

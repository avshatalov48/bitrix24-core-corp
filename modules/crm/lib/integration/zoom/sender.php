<?php

namespace Bitrix\Crm\Integration\Zoom;

use Bitrix\Main\Result;
use Bitrix\Main\Service\MicroService\BaseSender;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\License\UrlProvider;

class Sender extends BaseSender
{

	protected function getServiceUrl(): string
	{
		$domain = (new UrlProvider())->getTechDomain();
		$defaultServiceUrl = 'https://zoom.' . $domain . '/';

		return defined("ZOOM_SERVICE_URL") ? ZOOM_SERVICE_URL : $defaultServiceUrl;
	}

	public function test(): Result
	{
		return $this->performRequest("zoomcontroller.portalReceiver.test", []);
	}

	public function registerConference(array $conferenceData): Result
	{
		$sendData = [
			'id' => $conferenceData['id'],
			'uuid' => $conferenceData['uuid'],
			'externalUserId' => $conferenceData['externalUserId'],
			'externalAccountId' => $conferenceData['externalAccountId'],
			'startTime' => (new DateTime($conferenceData['start_time'], DATE_ATOM, new \DateTimeZone('UTC')))->getTimestamp(),
		];

		return $this->performRequest("zoomcontroller.portalreceiver.registerconference", $sendData);
	}
}

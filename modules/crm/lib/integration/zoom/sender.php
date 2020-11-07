<?php

namespace Bitrix\Crm\Integration\Zoom;

use Bitrix\Main\Result;
use Bitrix\Main\Service\MicroService\BaseSender;
use Bitrix\Main\Type\DateTime;

class Sender extends BaseSender
{
	protected const DEFAULT_SERVICE_URL = "https://zoom.bitrix.info/";

	protected function getServiceUrl(): string
	{
		return defined("ZOOM_SERVICE_URL") ? ZOOM_SERVICE_URL : static::DEFAULT_SERVICE_URL;
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
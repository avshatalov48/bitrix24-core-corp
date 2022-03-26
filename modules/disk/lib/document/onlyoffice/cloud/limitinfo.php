<?php

namespace Bitrix\Disk\Document\OnlyOffice\Cloud;

use Bitrix\Disk\Document\OnlyOffice\Configuration;
use Bitrix\Main\Result;

final class LimitInfo extends BaseSender
{
	public function getClientLimit(): Result
	{
		$clientId = (new Configuration())->getCloudRegistrationData()['clientId'];

		/** @see \Bitrix\DocumentProxy\Controller\LimitInfo::getClientLimitAction */
		return $this->performRequest('documentproxy.LimitInfo.getClientLimit', [
			'clientId' => $clientId,
		]);
	}
}
<?php

namespace Bitrix\Disk\Document\Flipchart\Cloud;

use Bitrix\Disk\Document\OnlyOffice\Cloud\BaseSender;
use Bitrix\Disk\Document\Flipchart\Configuration;
use Bitrix\Main\Result;

final class SignBoardConfig extends BaseSender
{
	public function sign(array $config): Result
	{
		$clientId = Configuration::getCloudRegistrationData()['clientId'] ?? null;

		/** @see \Bitrix\DocumentProxy\Controller\SignBoardConfiguration::signAction */
		$result = $this->performRequest('documentproxy.SignBoardConfiguration.sign', [
			'clientId' => $clientId,
			'config' => $config,
		]);

		return $result;
	}

	public function decode(string $config): Result
	{
		$clientId = Configuration::getCloudRegistrationData()['clientId'] ?? null;

		/** @see \Bitrix\DocumentProxy\Controller\SignBoardConfiguration::decodeJwtAction */
		$result = $this->performRequest('documentproxy.SignBoardConfiguration.decodeJwt', [
			'clientId' => $clientId,
			'data' => $config,
		]);

		return $result;
	}
}
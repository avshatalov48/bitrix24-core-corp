<?php

namespace Bitrix\Disk\Document\OnlyOffice\Cloud;

use Bitrix\Disk\Document\OnlyOffice\Configuration;
use Bitrix\Main\Result;

final class SingDocumentConfig extends BaseSender
{
	public function sign(array $config): Result
	{
		//there is no reason to send token because we'll rewrite it.
		unset($config['token']);

		$clientId = (new Configuration())->getCloudRegistrationData()['clientId'];

		/** @see \Bitrix\DocumentProxy\Controller\SignDocumentConfiguration::signAction */
		return $this->performRequest('documentproxy.SignDocumentConfiguration.sign', [
			'clientId' => $clientId,
			'config' => $config,
		]);
	}
}
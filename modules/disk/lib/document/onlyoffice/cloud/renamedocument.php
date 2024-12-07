<?php

namespace Bitrix\Disk\Document\OnlyOffice\Cloud;

use Bitrix\Disk\Document\OnlyOffice\Configuration;
use Bitrix\Main\Result;

final class RenameDocument extends BaseSender
{
	public function rename(array $operationData): Result
	{
		$clientId = (new Configuration())->getCloudRegistrationData()['clientId'];

		/** @see \Bitrix\DocumentProxy\Controller\CommandService::processAction */
		return $this->performRequest('documentproxy.CommandService.process', [
			'clientId' => $clientId,
			'body' => $operationData,
		]);
	}
}
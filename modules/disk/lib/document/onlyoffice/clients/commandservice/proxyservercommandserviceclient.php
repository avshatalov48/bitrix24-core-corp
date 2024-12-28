<?php

declare(strict_types=1);

namespace Bitrix\Disk\Document\OnlyOffice\Clients\CommandService;

use Bitrix\Disk\Document\OnlyOffice\Clients\CommandService\Commands\DropCommand;
use Bitrix\Disk\Document\OnlyOffice\Clients\CommandService\Commands\MetaCommand;
use Bitrix\Disk\Document\OnlyOffice\Cloud\BaseSender;
use Bitrix\Disk\Document\OnlyOffice\Configuration;
use Bitrix\Main\Result;

class ProxyServerCommandServiceClient extends BaseSender implements CommandServiceClientInterface
{
	private readonly string $clientId;

	public function __construct(string $serviceUrl)
	{
		parent::__construct($serviceUrl);
		$this->clientId = (new Configuration())->getCloudRegistrationData()['clientId'];
	}

	public function rename(string $documentKey, string $newName): Result
	{
		/** @see \Bitrix\DocumentProxy\Controller\CommandService::renameAction() */
		return $this->performRequest('documentproxy.CommandService.rename', [
			'clientId' => $this->clientId,
			'command' => new MetaCommand($documentKey, [
				'title' => $newName,
			]),
		]);
	}

	public function drop(string $documentKey, array $userIds): Result
	{
		/** @see \Bitrix\DocumentProxy\Controller\CommandService::dropAction */
		return $this->performRequest('documentproxy.CommandService.drop', [
			'clientId' => $this->clientId,
			'command' => new DropCommand($documentKey, $userIds),
		]);
	}

}
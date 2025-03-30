<?php

declare(strict_types=1);

namespace Bitrix\Disk\Document\OnlyOffice\Service;

use Bitrix\Disk\Document\Models\DocumentSession;
use Bitrix\Disk\Document\OnlyOffice\Clients\CommandService\CommandServiceClientFactory;
use Bitrix\Disk\Document\OnlyOffice\Clients\CommandService\CommandServiceClientInterface;
use Bitrix\Disk\Document\SessionTerminationService;
use Bitrix\Main\Config\ConfigurationException;
use Bitrix\Main\ObjectNotFoundException;
use Psr\Container\NotFoundExceptionInterface;

class OnlyOfficeSessionTerminationService implements SessionTerminationService
{
	private readonly array $userIds;
	private readonly int $objectId;
	private readonly CommandServiceClientInterface $commandServiceClient;

	/**
	 * @param int $objectId
	 * @param array $userIds
	 * @throws ConfigurationException
	 * @throws ObjectNotFoundException
	 * @throws NotFoundExceptionInterface
	 */
	public function __construct(int $objectId, array $userIds)
	{
		$this->userIds = $userIds;
		$this->objectId = $objectId;
		$this->commandServiceClient = CommandServiceClientFactory::createCommandServiceClient();
	}

	public function terminateAllSessions(): void
	{
		$localSessions = $this->getLocalSessions();

		if (empty($localSessions))
		{
			return;
		}

		$this->terminateExternalSession($localSessions);
		$this->deleteLocalSession($localSessions);
	}

	/**
	 * @param DocumentSession[] $localSessions
	 * @return void
	 */
	private function deleteLocalSession(array $localSessions): void
	{
		foreach ($localSessions as $session)
		{
			$session->delete();
		}
	}

	/**
	 * @param DocumentSession[] $localSessions
	 * @return void
	 */
	private function terminateExternalSession(array $localSessions): void
	{
		foreach ($localSessions as $session)
		{
			$documentKey = $session->getExternalHash();
			$userIds = [(string)$session->getUserId()];
			$this->commandServiceClient->drop($documentKey, $userIds);
		}
	}

	private function getLocalSessions(): array
	{
		return DocumentSession::getModelList([
			'filter' => [
				'OBJECT_ID' => $this->objectId,
				'USER_ID' => $this->userIds,
				'STATUS' => DocumentSession::STATUS_ACTIVE,
			]
		]);
	}
}
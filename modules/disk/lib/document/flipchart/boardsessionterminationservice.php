<?php

declare(strict_types=1);

namespace Bitrix\Disk\Document\Flipchart;

use Bitrix\Disk\Document\Models\DocumentSession;
use Bitrix\Disk\Document\SessionTerminationService;
use Bitrix\Disk\File;
use Bitrix\Main\NotImplementedException;

class BoardSessionTerminationService implements SessionTerminationService
{
	private readonly array $userIds;
	private readonly int $objectId;

	/**
	 * @param int $objectId
	 * @param array $userIds

	 */
	public function __construct(int $objectId, array $userIds)
	{
		$this->userIds = $userIds;
		$this->objectId = $objectId;
	}

	public function terminateAllSessions(): void
	{
		$localSessions = $this->getLocalSessions();

		if (empty($localSessions))
		{
			return;
		}

		$this->deleteLocalSession($localSessions);

		$file = File::loadById($this->objectId);
		if ($file)
		{
			BoardService::kickUsers($file, $this->userIds);
		}
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
	 * @throws NotImplementedException
	 */
	private function terminateExternalSession(array $localSessions): void
	{
		throw new NotImplementedException();
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
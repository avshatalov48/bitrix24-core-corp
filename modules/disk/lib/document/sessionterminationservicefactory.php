<?php

declare(strict_types=1);

namespace Bitrix\Disk\Document;

use Bitrix\Disk\Document\Models\DocumentService;
use Bitrix\Disk\Document\Models\DocumentSession;
use Bitrix\Disk\Document\OnlyOffice\Service\OnlyOfficeSessionTerminationService;
use Throwable;

class SessionTerminationServiceFactory
{

	private NullSessionTerminationService $nullService;

	public function __construct(
		private readonly int $objectId,
		private readonly array $userIds,
	)
	{
		$this->nullService = new NullSessionTerminationService();
	}

	public function create(): SessionTerminationService
	{
		$session = $this->getObjectSession($this->objectId);

		if ($session === null || $session->getService() === null)
		{
			return $this->nullService;
		}

		return match ($session->getService()) {
			DocumentService::OnlyOffice => $this->getOnlyOfficeService(),
			default => $this->nullService,
		};
	}

	private function getObjectSession(int $objectId): ?DocumentSession
	{
		$sessionList = DocumentSession::getModelList([
			'filter' => [
				'OBJECT_ID' => $objectId,
				'STATUS' => DocumentSession::STATUS_ACTIVE,
			],
			'limit' => 1,
		]);

		return $sessionList[0] ?? null;
	}

	private function getOnlyOfficeService(): SessionTerminationService
	{
		try
		{
			return new OnlyOfficeSessionTerminationService($this->objectId, $this->userIds);
		}
		catch (Throwable)
		{
			return $this->nullService;
		}
	}
}
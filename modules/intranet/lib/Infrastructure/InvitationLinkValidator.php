<?php

namespace Bitrix\Intranet\Infrastructure;

use Bitrix\Intranet\Contract\Repository\InvitationLinkRepository as InvitationLinkRepositoryContract;
use Bitrix\Intranet\Enum\LinkEntityType;
use Bitrix\Intranet\Service\ServiceContainer;

class InvitationLinkValidator
{
	private InvitationLinkRepositoryContract $invitationLinkRepository;

	public function __construct(
		private int            $entityId,
		private LinkEntityType $entityType,
	)
	{
		$this->invitationLinkRepository = ServiceContainer::getInstance()->invitationLinkRepository();
	}

	public function validete(string $code): bool
	{
		$entity = $this->invitationLinkRepository->getActualByEntity(
			$this->entityType,
			$this->entityId
		);
		if ($entity)
		{
			return $entity->getCode() === $code;
		}

		return false;
	}
}
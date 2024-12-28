<?php

namespace Bitrix\Intranet\Infrastructure;

use Bitrix\Intranet\Entity\InvitationLink;
use Bitrix\Intranet\Enum\LinkEntityType;
use Bitrix\Intranet\Contract\Repository\InvitationLinkRepository as InvitationLinkRepositoryContract;
use Bitrix\Intranet\Service\ServiceContainer;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Type\DateTime;

class LinkCodeGenerator
{
	const LENGTH_СODE = 15;
	private InvitationLinkRepositoryContract $invitationLinkRepository;

	public function __construct(
		private int            $entityId,
		private LinkEntityType $entityType,
	)
	{
		$this->invitationLinkRepository = ServiceContainer::getInstance()->invitationLinkRepository();
	}

	public static function createByCollabId(int $collabId): self
	{
		return new self($collabId, LinkEntityType::COLLAB);
	}

	/**
	 * This method deletes the old record and inserts a new one
	 *
	 * @param DateTime|null $expiredDate
	 * @return InvitationLink
	 */
	public function generate(?DateTime $expiredDate = null): InvitationLink
	{
		$entity = $this->invitationLinkRepository->getByEntity(
			$this->entityType,
			$this->entityId
		);

		if ((int)$entity?->getId() > 0)
		{
			$this->invitationLinkRepository->delete($entity->getId());
		}

		$entity = new InvitationLink(
			$this->entityId,
			$this->entityType,
			Random::getString(self::LENGTH_СODE),
			expiredAt: $expiredDate
		);

		return $this->invitationLinkRepository->create($entity);
	}

	/**
	 * This method returns the actual record or generates a new record
	 *
	 * @param DateTime|null $expiredDate
	 * @return InvitationLink
	 */
	public function getOrGenerate(?DateTime $expiredDate = null): InvitationLink
	{
		$entity = $this->invitationLinkRepository->getActualByEntity(
			$this->entityType,
			$this->entityId
		);

		if (!$entity)
		{
			return $this->generate($expiredDate);
		}

		return $entity;
	}

	public function check(string $code): bool
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
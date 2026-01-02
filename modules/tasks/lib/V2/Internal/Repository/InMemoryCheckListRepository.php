<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\V2\Internal\Entity;

class InMemoryCheckListRepository implements CheckListRepositoryInterface
{
	private CheckListRepositoryInterface $checkListRepository;

	private array $idsCache = [];
	private array $attachmentCache = [];

	public function __construct(CheckListRepository $checkListRepository)
	{
		$this->checkListRepository = $checkListRepository;
	}

	public function getIdsByEntity(int $entityId, Entity\CheckList\Type $type): array
	{
		$key = "{$entityId}_{$type->name}";
		if (!isset($this->idsCache[$key]))
		{
			$this->idsCache[$key] = $this->checkListRepository->getIdsByEntity($entityId, $type);
		}

		return $this->idsCache[$key];
	}

	public function getAttachmentIdsByEntity(int $entityId, Entity\CheckList\Type $type): array
	{
		$key = "{$entityId}_{$type->name}";
		if (!isset($this->attachmentCache[$key]))
		{
			$this->attachmentCache[$key] = $this->checkListRepository->getAttachmentIdsByEntity($entityId, $type);
		}

		return $this->attachmentCache[$key];
	}
}

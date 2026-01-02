<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;
use Bitrix\Tasks\V2\Internal\Entity;

interface CheckListRepositoryInterface
{
	public function getIdsByEntity(int $entityId, Entity\CheckList\Type $type): array;

	public function getAttachmentIdsByEntity(int $entityId, Entity\CheckList\Type $type): array;
}

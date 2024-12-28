<?php

namespace Bitrix\Intranet\Contract\Repository;

use Bitrix\Intranet\Entity\InvitationLink;
use Bitrix\Intranet\Enum\LinkEntityType;

interface InvitationLinkRepository
{
	public function getByEntity(LinkEntityType $entitytype, int $entityId): ?InvitationLink;

	public function getActualByEntity(LinkEntityType $entitytype, int $entityId): ?InvitationLink;

	public function create(InvitationLink $entity): InvitationLink;

	public function delete(int $id): bool;
}
<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\V2\Internal\Entity\Template;

interface TemplateRepositoryInterface
{
	public function getById(int $id): ?Template;

	public function save(Template $entity, int $userId): int;

	public function delete(int $id, int $userId): void;
}

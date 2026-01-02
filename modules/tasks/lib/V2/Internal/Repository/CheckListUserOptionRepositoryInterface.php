<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\V2\Internal\Entity;

interface CheckListUserOptionRepositoryInterface
{
	public function get(int $itemId, int $userId): Entity\CheckList\UserOptionCollection;

	public function isSet(int $userId, array $itemIds, array $optionCodes = []): array;

	public function add(Entity\CheckList\UserOption $userOption): void;

	public function delete(int $userId, int $itemId = 0, array $codes = []): void;
}

<?php

namespace Bitrix\HumanResources\Contract\Repository\Access;

use Bitrix\HumanResources\Model\NodeCollection;

interface AccessNodeRepository
{
	public function isDepartmentUser(
		int $nodeId,
		int $userId,
		bool $checkSubdepartments = false
	): bool;
}
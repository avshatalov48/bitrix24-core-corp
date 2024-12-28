<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Dto;

class DeleteRoleIdForUserByCodesDto
{
	public int $userId;
	/** @var list<array{int, int[]}>  */
	public array $ownerIdsForSharingRoles;
	public bool $needDeleted = false;

	/** @var array int[] */
	public array $roleIds;

	/** @var array string[] */
	public array $roleCodes;
}

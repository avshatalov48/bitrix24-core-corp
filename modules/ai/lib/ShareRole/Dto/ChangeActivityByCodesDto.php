<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Dto;

class ChangeActivityByCodesDto
{
	public int $userId;
	/** @var string[]  */
	public array $roleCodes;
	/** @var int[] */
	public array $roleIds;
	public bool $needActivate = false;
}

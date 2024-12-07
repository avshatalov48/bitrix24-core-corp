<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Dto;

class DeletePromptIdForUserByCodesDto
{
	public int $userId;
	/** @var list<array{int, int[]}>  */
	public array $ownerIdsForSharingPrompts;
	public bool $needDeleted = false;

	/** @var array int[] */
	public array $promptIds;

	/** @var array string[] */
	public array $promptCodes;
}

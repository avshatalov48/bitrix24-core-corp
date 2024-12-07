<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Dto;

use Bitrix\AI\SharePrompt\Events\Enums\ShareType;

class DeletePromptIdForUserDto
{
	public int $userId;
	public bool $hasInOwnerTable = false;
	public bool $needDeleted = false;
	public int $promptId;
	public string $promptCode;
	public ShareType $shareType;
}

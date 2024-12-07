<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Dto;

use Bitrix\AI\SharePrompt\Events\Enums\ShareType;

class ChangeActivityDto
{
	public int $userId;
	public int $promptId;
	public string $promptCode;
	public bool $needActivate = false;
	public ShareType $shareType;
}

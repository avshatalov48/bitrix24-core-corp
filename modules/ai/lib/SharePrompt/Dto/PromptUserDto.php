<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Dto;

class PromptUserDto
{
	public int $promptId;
	public string $promptCode;
	public int $userId;
	public string $text = '';
}

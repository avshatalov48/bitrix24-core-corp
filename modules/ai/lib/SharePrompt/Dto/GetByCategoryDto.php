<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Dto;

class GetByCategoryDto
{
	public string $category;
	public int $userId;
	public string $moduleId;
	public string $context;
	public string $userLang;
}

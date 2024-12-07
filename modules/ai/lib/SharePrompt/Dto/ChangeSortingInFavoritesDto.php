<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Dto;

class ChangeSortingInFavoritesDto
{
	public int $userId;
	public array $promptIds;
	public array $promptCodes;
}

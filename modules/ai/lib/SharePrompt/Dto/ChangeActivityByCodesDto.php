<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Dto;

class ChangeActivityByCodesDto
{
	public int $userId;
	/** @var string[]  */
	public array $promptCodes;
	/** @var int[] */
	public array $promptIds;
	public bool $needActivate = false;
}

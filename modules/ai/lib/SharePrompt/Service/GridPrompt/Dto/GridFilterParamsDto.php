<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Service\GridPrompt\Dto;

use Bitrix\Main\Type\DateTime;

class GridFilterParamsDto
{
	public string $name = '';
	public array $types = [];
	public array $authors = [];
	public array $editors = [];
	public array $share = [];
	public ?bool $isActive = null;
	public ?bool $isDeleted = null;
	public array $promptIds = [];
	public array $categories = [];

	public ?DateTime $dateModifyStart = null;
	public ?DateTime $dateModifyEnd = null;

	public ?DateTime $dateCreateStart = null;
	public ?DateTime $dateCreateEnd = null;
}

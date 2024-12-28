<?php

namespace Bitrix\AI\ShareRole\Service\GridRole\Dto;

use Bitrix\Main\Type\DateTime;

class GridFilterParamsDto
{
	public string $name = '';
	public array $authors = [];
	public array $editors = [];
	public array $share = [];
	public ?bool $isActive = null;
	public ?bool $isDeleted = null;
	public array $roleIds = [];

	public ?DateTime $dateModifyStart = null;
	public ?DateTime $dateModifyEnd = null;

	public ?DateTime $dateCreateStart = null;
	public ?DateTime $dateCreateEnd = null;
}
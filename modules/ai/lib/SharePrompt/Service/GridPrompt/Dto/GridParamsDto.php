<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Service\GridPrompt\Dto;

class GridParamsDto
{
	public array $order = [];
	public GridFilterParamsDto $filter;
	public int $limit = 20;
	public int $offset = 0;
}

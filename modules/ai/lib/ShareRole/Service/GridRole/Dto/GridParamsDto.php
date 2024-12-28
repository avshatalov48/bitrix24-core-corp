<?php

namespace Bitrix\AI\ShareRole\Service\GridRole\Dto;

class GridParamsDto
{
	public array $order = [];
	public GridFilterParamsDto $filter;
	public int $limit = 20;
	public int $offset = 0;
}
<?php

namespace Bitrix\Mobile\UI\Kanban\Dto;

use Bitrix\Mobile\Dto\Dto;

class Stage extends Dto
{
	public int $id;

	public string $name;

	public int $sort;

	public string $color;

	public string $statusId;

	/**
	 * @var array<string, mixed>
	 */
	public array $counters = [];
}

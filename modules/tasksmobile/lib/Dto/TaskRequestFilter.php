<?php

declare(strict_types=1);

namespace Bitrix\TasksMobile\Dto;

use Bitrix\Mobile\Dto\Dto;
use Bitrix\TasksMobile\Provider\TaskProvider;

final class TaskRequestFilter extends Dto
{
	public int $ownerId = 0;
	public int $flowId = 0;
	public int $creatorId = 0;
	public string $searchString = '';
	public string $presetId = TaskProvider::PRESET_NONE;
	public string $counterId = TaskProvider::COUNTER_NONE;
}
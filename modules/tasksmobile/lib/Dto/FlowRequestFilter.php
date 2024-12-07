<?php

declare(strict_types=1);

namespace Bitrix\TasksMobile\Dto;

use Bitrix\Mobile\Dto\Dto;
use Bitrix\TasksMobile\Provider\FlowProvider;

class FlowRequestFilter extends Dto
{
	public string $searchString = '';
	public string $presetId = FlowProvider::PRESET_NONE;
	public string $counterId = FlowProvider::COUNTER_NONE;
	public int $creatorId = 0;
	public int $excludedFlowId = 0;
}
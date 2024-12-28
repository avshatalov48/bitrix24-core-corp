<?php

namespace Bitrix\TasksMobile\FlowAiAdvice\Dto;

use Bitrix\Mobile\Dto\Dto;

class FlowAiAdviceDto extends Dto
{
	public int $minTasksCountForAdvice;
	public int $efficiencyThreshold;
	public array $advices;
}

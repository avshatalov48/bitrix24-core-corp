<?php

declare(strict_types=1);

namespace Bitrix\TasksMobile\Dto;

use Bitrix\Mobile\Dto\Dto;

final class TaskStageDto extends Dto
{
	public int $taskId;
	public int $stageId;
	public int $userId;
	public string $viewMode;
	public bool $canMoveStage;
}

<?php

namespace Bitrix\Crm\History\StageHistoryWithSupposed;

use Bitrix\Crm\Dto\Dto;

final class TransitionDto extends Dto
{
	public ?int $categoryId;
	public string $stageId;
	public string $semantics;
	public bool $isSupposed;
}

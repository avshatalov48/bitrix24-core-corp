<?php

namespace Bitrix\TasksMobile\Dto;

final class Stage extends \Bitrix\Mobile\UI\Kanban\Dto\Stage
{
	public ?string $entityType;

	public ?int $deadline;

	public ?int $leftBorder;

	public ?int $rightBorder;
}

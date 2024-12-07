<?php

declare(strict_types=1);

namespace Bitrix\TasksMobile\Dto;

use Bitrix\Mobile\Dto\Dto;

final class DodTypesDto extends Dto
{
	public int $id;
	public int $entityId;
	public int $sort;
	public string $name;
	public string $dodRequired;
	public array $participants;
}

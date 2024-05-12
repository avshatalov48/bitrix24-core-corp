<?php

namespace Bitrix\CrmMobile\Dto;

use Bitrix\Mobile\Dto\Attributes\Collection;

final class Stage extends \Bitrix\Mobile\UI\Kanban\Dto\Stage
{
	public string $semantics;

	#[Collection(Tunnel::class)]
	public array $tunnels = [];
}

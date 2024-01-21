<?php

namespace Bitrix\CrmMobile\Dto;

use Bitrix\Mobile\Dto\Type;

final class Stage extends \Bitrix\Mobile\UI\Kanban\Dto\Stage
{
	public string $semantics;

	/** @var Tunnel[] */
	public array $tunnels = [];

	public function getCasts(): array
	{
		return [
			'tunnels' => Type::collection(Tunnel::class),
		];
	}
}

<?php

namespace Bitrix\Crm\Service\Router\Dto;

use JsonSerializable;

final class RouterAnchor implements JsonSerializable
{
	/**
	 * @param SidePanelAnchorRule $rule
	 * @param string[] $roots
	 */
	public function __construct(
		private readonly SidePanelAnchorRule $rule,
		private readonly array $roots,
	)
	{
	}

	public function jsonSerialize(): array
	{
		return [
			'roots' => $this->roots,
			'rule' => $this->rule->jsonSerialize(),
		];
	}
}

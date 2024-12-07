<?php

namespace Bitrix\Sign\Engine\ActionFilter;

class RuleWithPayload
{
	public function __construct(
		public string $accessPermission,
		public ?string $itemType = null,
		public ?string $itemIdRequestKey = null,
		public ?bool $passes = null,
	) {}
}

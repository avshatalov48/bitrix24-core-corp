<?php

namespace Bitrix\Sign\Item\Connector;

class FetchRequisiteModifier
{
	public function __construct(
		public ?int $presetId = null,
	)
	{
	}
}
<?php

namespace Bitrix\Sign\Attribute\Type;

#[\Attribute]
class AsInt
{
	public function __construct(
		public int $value,
	)
	{
	}
}
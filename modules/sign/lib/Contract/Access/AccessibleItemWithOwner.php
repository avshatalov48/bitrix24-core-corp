<?php

namespace Bitrix\Sign\Contract\Access;

interface AccessibleItemWithOwner extends AccessibleItem
{
	public function getOwnerId(): int;
}
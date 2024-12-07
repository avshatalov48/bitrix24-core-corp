<?php

namespace Bitrix\Sign\Contract\Access;

interface AccessibleItemWithOwner extends \Bitrix\Main\Access\AccessibleItem
{
	public function getOwnerId(): int;
}
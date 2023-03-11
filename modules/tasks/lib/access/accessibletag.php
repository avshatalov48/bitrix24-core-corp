<?php

namespace Bitrix\Tasks\Access;

use Bitrix\Main\Access\AccessibleItem;

interface AccessibleTag extends AccessibleItem
{
	public function getOwner(): int;
}
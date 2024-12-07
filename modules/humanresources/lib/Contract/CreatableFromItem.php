<?php

namespace Bitrix\HumanResources\Contract;

interface CreatableFromItem
{
	public static function createFromItem(Item $item): static;
}

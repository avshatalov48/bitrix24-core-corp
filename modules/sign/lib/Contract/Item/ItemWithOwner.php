<?php

namespace Bitrix\Sign\Contract\Item;

interface ItemWithOwner
{
	public function getId(): int;
	public function getOwnerId(): int;
}
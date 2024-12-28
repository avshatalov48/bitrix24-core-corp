<?php

namespace Bitrix\Sign\Contract\Chat;

use Bitrix\Sign\Contract\Item;

interface GroupChatMessage extends Item
{
	public function getText(): string;
	public function getParams(): array;
}

<?php

namespace Bitrix\Sign\Contract\Item;

interface ItemWithCrmId
{
	public function getCrmId(): int;
}

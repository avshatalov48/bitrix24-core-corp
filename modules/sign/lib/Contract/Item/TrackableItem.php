<?php

namespace Bitrix\Sign\Contract\Item;

interface TrackableItem
{
	public function getOriginal(): array;
	public function initOriginal(): void;
}
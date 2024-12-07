<?php

namespace Bitrix\Intranet\Counters;

interface SyncInterface
{
	public function sync(Counter $counter): void;
}
<?php

namespace Bitrix\Crm\Activity\FastSearch\Sync\Command;


interface SyncCommand
{
	public function execute(): void;
}
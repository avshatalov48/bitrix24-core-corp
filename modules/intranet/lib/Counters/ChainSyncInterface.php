<?php

namespace Bitrix\Intranet\Counters;

interface ChainSyncInterface extends SyncInterface
{
	public function setNext(ChainSyncInterface $next): void;

	public function next(): ?ChainSyncInterface;
}
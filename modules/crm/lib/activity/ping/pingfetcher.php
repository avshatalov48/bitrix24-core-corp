<?php

namespace Bitrix\Crm\Activity\Ping;

interface PingFetcher
{
	public function fetchAll(): array;

	public function fetchSelectedValues(): array;

	public function fetchForJsComponent(): array;

	public function getCurrentOffsets(): array;
}
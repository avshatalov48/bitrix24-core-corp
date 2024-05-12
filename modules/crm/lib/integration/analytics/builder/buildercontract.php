<?php

namespace Bitrix\Crm\Integration\Analytics\Builder;

use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;

interface BuilderContract
{
	public function validate(): Result;

	public function buildUri(string|Uri $baseUri): Uri;

	public function buildData(): array;

	public function buildEvent(): \Bitrix\Main\Analytics\AnalyticsEvent;
}

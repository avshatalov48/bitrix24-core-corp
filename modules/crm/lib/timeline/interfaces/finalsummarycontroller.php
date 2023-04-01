<?php

namespace Bitrix\Crm\Timeline\Interfaces;

use Bitrix\Crm\Item;

interface FinalSummaryController
{
	public function onCreateFinalSummary(Item $item): void;
}

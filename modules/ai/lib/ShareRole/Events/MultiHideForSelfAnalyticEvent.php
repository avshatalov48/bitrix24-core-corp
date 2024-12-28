<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Events;

class MultiHideForSelfAnalyticEvent extends BaseAnalyticEvent
{

	public function getEventName(): string
	{
		return 'multi_hide_for_self';
	}
}

<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Events;

class MultiShowForSelfAnalyticEvent extends BaseAnalyticEvent
{
	public function getEventName(): string
	{
		return 'multi_show_for_self';
	}
}

<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Events;

class MultiDeactivateAnalyticEvent extends BaseAnalyticEvent
{
	public function getEventName(): string
	{
		return 'multi_deactivate';
	}
}

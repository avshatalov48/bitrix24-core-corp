<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Events;

class MultiActivateAnalyticEvent extends BaseAnalyticEvent
{
	public function getEventName(): string
	{
		return 'multi_activate';
	}
}

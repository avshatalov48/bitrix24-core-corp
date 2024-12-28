<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Events;

class MultiDeactivateAnalyticEvent extends BaseAnalyticEvent
{

	public function getEventName(): string
	{
		return 'multi_deactivate';
	}
}

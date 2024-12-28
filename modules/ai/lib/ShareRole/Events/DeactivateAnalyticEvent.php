<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Events;

class DeactivateAnalyticEvent extends BaseAnalyticEvent
{

	public function getEventName(): string
	{
		return 'deactivate';
	}
}

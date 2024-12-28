<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Events;

class ActivateAnalyticEvent extends BaseAnalyticEvent
{

	public function getEventName(): string
	{
		return 'activate';
	}
}

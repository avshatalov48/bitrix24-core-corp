<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Events;

class ShowForSelfAnalyticEvent extends BaseAnalyticEvent
{

	public function getEventName(): string
	{
		return 'show_for_self';
	}
}

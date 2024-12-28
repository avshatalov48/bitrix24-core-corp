<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Events;

class HideForSelfAnalyticEvent extends BaseAnalyticEvent
{

	public function getEventName(): string
	{
		return 'hide_for_self';
	}
}

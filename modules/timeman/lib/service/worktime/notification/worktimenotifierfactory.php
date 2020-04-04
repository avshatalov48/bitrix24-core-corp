<?php
namespace Bitrix\Timeman\Service\Worktime\Notification;

use Bitrix\Timeman\Service\Notification\InstantMessageNotifier;

class WorktimeNotifierFactory
{
	public function getViolationNotifier($schedule)
	{
		return new InstantMessageNotifier();
	}
}
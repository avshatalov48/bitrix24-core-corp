<?php

namespace Bitrix\CalendarMobile\Controller;

use Bitrix\Calendar\UserSettings;
use Bitrix\Main\Engine\Controller;

class Settings extends Controller
{
	private int $userId;

	protected function init(): void
	{
		parent::init();

		$this->userId = \CCalendar::GetUserId();
	}

	public function setDenyBusyInvitationAction(string $denyBusyInvitation): void
	{
		if (!$this->userId)
		{
			return;
		}

		$settings = [
			'denyBusyInvitation' => $denyBusyInvitation === 'Y',
		];

		UserSettings::set($settings, $this->userId);
	}

	public function setShowWeekNumbersAction(string $showWeekNumbers): void
	{
		if (!$this->userId)
		{
			return;
		}

		$settings = [
			'showWeekNumbers' => $showWeekNumbers === 'Y' ? 'Y' : 'N',
		];

		UserSettings::set($settings, $this->userId);
	}

	public function setShowDeclinedAction(string $showDeclined): void
	{
		if (!$this->userId)
		{
			return;
		}

		$settings = [
			'showDeclined' => $showDeclined === 'Y',
		];

		UserSettings::set($settings, $this->userId);
	}
}

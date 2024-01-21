<?php

namespace Bitrix\CalendarMobile\AhaMoments;

abstract class Base
{
	protected const OPTION_CATEGORY = 'calendarmobile.aha-moment';
	protected const OPTION_NAME = '';

	public function setViewed(): void
	{
		\CUserOptions::SetOption(static::OPTION_CATEGORY, static::OPTION_NAME, 'Y');
	}

	public function canShow(): bool
	{
		return !$this->isSeenByUser();
	}

	protected function isSeenByUser(): bool
	{
		return \CUserOptions::GetOption(static::OPTION_CATEGORY, static::OPTION_NAME, 'N') === 'Y';
	}
}
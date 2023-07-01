<?php

namespace Bitrix\CrmMobile\AhaMoments;

use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Config\Option;
use CUserOptions;

abstract class Base
{
	use Singleton;

	protected const OPTION_CATEGORY = 'crmmobile.aha-moment';
	protected const OPTION_NAME = '';

	public function setViewed(): void
	{
		// format similar to the web version
		$value = [
			'closed' => 'Y',
		];

		CUserOptions::SetOption(static::OPTION_CATEGORY, static::OPTION_NAME, $value);
	}

	public function canShow(): bool
	{
		return (
			!$this->isUserSeenTour()
			&& Option::get('crmmobile', 'release-spring-2023', true)
		);
	}

	protected function isUserSeenTour(): bool
	{
		$option = CUserOptions::GetOption(static::OPTION_CATEGORY, static::OPTION_NAME, []);

		return (isset($option['closed']) && $option['closed'] === 'Y');
	}

	protected function getOptionName(): string
	{
		return static::OPTION_NAME;
	}
}

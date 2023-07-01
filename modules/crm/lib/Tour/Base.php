<?php

namespace Bitrix\Crm\Tour;

use Bitrix\Crm\Traits;
use Bitrix\Main\Config\Option;
use CUserOptions;

abstract class Base
{
	use Traits\Singleton;

	protected const OPTION_CATEGORY = 'crm.tour';
	protected const OPTION_NAME = '';

	/**
	 * Determine whether to show a tour
	 *
	 * @return bool
	 */
	abstract protected function canShow(): bool;

	public function build(): string
	{
		if (!$this->canShow())
		{
			return '';
		}
		if ($this->isDeactivated())
		{
			return '';
		}

		ob_start();
		global $APPLICATION;
		$APPLICATION->IncludeComponent(
			'bitrix:crm.whats_new',
			$this->getComponentTemplate(),
			[
				'SLIDES' => $this->getSlides(),
				'STEPS' => $this->getSteps(),
				'OPTIONS' => $this->getOptions(),
				'CLOSE_OPTION_CATEGORY' => $this->getOptionCategory(),
				'CLOSE_OPTION_NAME' => $this->getOptionName(),
			],
		);

		return ob_get_clean();
	}

	protected function isUserSeenTour(): bool
	{
		$option = CUserOptions::GetOption(static::OPTION_CATEGORY, static::OPTION_NAME, []);

		return (isset($option['closed']) && $option['closed'] === 'Y');
	}

	protected function getComponentTemplate(): string
	{
		return '';
	}

	protected function getSlides(): array
	{
		return [];
	}

	protected function getSteps(): array
	{
		return [];
	}

	protected function getOptions(): array
	{
		return [];
	}

	protected function getOptionCategory(): string
	{
		return static::OPTION_CATEGORY;
	}

	protected function getOptionName(): string
	{
		return static::OPTION_NAME;
	}

	private function isDeactivated(): bool
	{
		return (Option::get(static::OPTION_CATEGORY, 'HIDE_ALL_TOURS', 'N') === 'Y');
	}
}

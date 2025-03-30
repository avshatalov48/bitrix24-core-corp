<?php

namespace Bitrix\Crm\Tour;

use Bitrix\Crm\Settings\Crm;
use Bitrix\Crm\Traits;
use Bitrix\Main\Type\DateTime;

abstract class Base
{
	use Traits\Singleton;

	private const COMPONENT_NAME = 'bitrix:crm.whats_new';

	protected const OPTION_CATEGORY = 'crm.tour';
	protected const OPTION_NAME = '';

	/**
	 * Number of times the tour was shown (Default: 1)
	 *
	 * @return int
	 */
	protected int $numberOfViewsLimit = 1;

	protected function __construct()
	{
	}

	/**
	 * Determine whether to show a tour
	 *
	 * @return bool
	 */
	abstract protected function canShow(): bool;

	public function build(): string
	{
		if ($this->isBuildComponentDisabled())
		{
			return '';
		}

		ob_start();
		global $APPLICATION;
		$APPLICATION->IncludeComponent(
			self::COMPONENT_NAME,
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

	protected function isBuildComponentDisabled(): bool
	{
		return $this->isShowDeadlineExpired()
			|| !$this->isAvailablePortal()
			|| Config::isToursDeactivated($this->getOptionCategory())
			|| $this->isNumberOfViewsExceeded()
			|| !$this->canShow()
		;
	}

	protected function isUserSeenTour(): bool
	{
		$option = Config::getPersonalValue(
			static::OPTION_CATEGORY,
			static::OPTION_NAME,
			Config::CODE_CLOSED
		);

		return ($option ?? null) === 'Y';
	}

	protected function isNumberOfViewsExceeded(): bool
	{
		$option = Config::getPersonalValue(
			static::OPTION_CATEGORY,
			static::OPTION_NAME,
			Config::CODE_NUMBER_OF_VIEWS
		);

		if (isset($option))
		{
			$currentNumberOfViews = (int)$option;
		}
		else
		{
			$currentNumberOfViews = $this->isUserSeenTour() ? 1 : 0;
		}

		return $currentNumberOfViews > $this->numberOfViewsLimit;
	}

	protected function isMultipleViewsAllowed(): bool
	{
		return $this->numberOfViewsLimit > 1;
	}

	protected function getShowDeadline(): ?DateTime
	{
		return null;
	}

	final protected function isShowDeadlineExpired(): bool
	{
		$deadline = $this->getShowDeadline();
		if ($deadline === null)
		{
			return false;
		}

		$now = new DateTime();

		return $now->getTimestamp() > $deadline->getTimestamp();
	}

	protected function getPortalMaxCreatedDate(): ?DateTime
	{
		return null;
	}

	final protected function isAvailablePortal(): bool
	{
		$date = $this->getPortalMaxCreatedDate();
		if ($date === null)
		{
			return true;
		}

		return Crm::isPortalCreatedBefore($date->getTimestamp());
	}
}

<?php

namespace Bitrix\Crm\RepeatSale;

use Bitrix\Crm\Feature;
use Bitrix\Crm\RepeatSale\Statistics\LimitChecker;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;

class AvailabilityChecker
{
	public const SEGMENT_INITIALIZATION_OPTION_NAME = 'repeat_sale_segment_initialization';
	public const ENABLE_PENDING_OPTION_NAME = 'repeat_sale_enable_pending';

	public function isAvailable(): bool
	{
		return
			$this->isEnabled()
			&& !$this->isSegmentsInitializationProgress()
			&& $this->isItemsCountsLessThenLimit()
		;
	}

	public function isEnabled(): bool
	{
		$region = mb_strtolower(Application::getInstance()->getLicense()->getRegion() ?? 'en');
		if ($region === 'cn')
		{
			return false;
		}

		$isFeatureEnabled = Feature::enabled(Feature\RepeatSale::class);
		$intranetToolManager = Container::getInstance()->getIntranetToolsManager();

		return $isFeatureEnabled && $intranetToolManager->checkRepeatSaleAvailability();
	}

	public function isItemsCountsLessThenLimit(): bool
	{
		return !LimitChecker::getInstance()->isLimitExceeded();
	}

	public function isSegmentsInitializationProgress(): bool
	{
		return Option::get('crm', self::SEGMENT_INITIALIZATION_OPTION_NAME, 'N') === 'Y';
	}

	public function isEnablePending(): bool
	{
		return Option::get('crm', self::ENABLE_PENDING_OPTION_NAME, 'N') === 'Y';
	}

	public function hasPermission(): bool
	{
		return RestrictionManager::getRepeatSaleRestriction()->hasPermission();
	}
}

<?php

namespace Bitrix\Crm\Controller\RepeatSale;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\RepeatSale\Log\Controller\RepeatSaleLogController;
use Bitrix\Crm\RepeatSale\Statistics\DataProvider;
use Bitrix\Crm\RepeatSale\Statistics\PeriodType;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Error;

final class Statistics extends Base
{
	// region actions
	public function getDataAction(?PeriodType $periodType = null): array
	{
		$availabilityChecker = Container::getInstance()->getRepeatSaleAvailabilityChecker();
		if (
			!$availabilityChecker->isAvailable()
			|| !$availabilityChecker->hasPermission()
			|| !$availabilityChecker->isItemsCountsLessThenLimit()
			|| !Container::getInstance()->getUserPermissions()->repeatSale()->canRead()
		)
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return [];
		}

		if ($periodType === null)
		{
			$this->addError(new Error('Unknown statistics period'));

			return [];
		}

		return (new DataProvider(RepeatSaleLogController::getInstance()))->getData($periodType);
	}
	// endregion

	public function getAutoWiredParameters(): array
	{
		return [
			new ExactParameter(
				PeriodType::class,
				'periodType',
				static fn($className, $periodTypeId) => PeriodType::tryFrom($periodTypeId) ?? PeriodType::Day30,
			),
		];
	}
}

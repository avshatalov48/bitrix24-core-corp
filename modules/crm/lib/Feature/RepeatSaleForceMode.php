<?php

namespace Bitrix\Crm\Feature;

use Bitrix\Crm\Feature;
use Bitrix\Crm\Feature\Category\BaseCategory;
use Bitrix\Crm\Feature\Category\Common;
use Bitrix\Crm\RepeatSale\FlowController;
use Bitrix\Crm\RepeatSale\Logger;
use Bitrix\Crm\RepeatSale\Segment\Controller\RepeatSaleSegmentController;
use Bitrix\Crm\RepeatSale\Segment\SegmentItem;
use Bitrix\Crm\RepeatSale\Segment\SegmentManager;
use Bitrix\Main\Localization\Loc;

class RepeatSaleForceMode extends BaseFeature
{
	public function getName(): string
	{
		return Loc::getMessage('CRM_FEATURE_REPEAT_SALE_FORCE_MODE_NAME');
	}

	public function getCategory(): BaseCategory
	{
		return Common::getInstance();
	}

	protected function getOptionName(): string
	{
		return 'CRM_REPEAT_SALE_FORCE_MODE';
	}

	protected function getEnabledValue(): bool
	{
		return true;
	}

	public function enable(): void
	{
		if ($this->isEnabled() || FlowController::getInstance()->getEnableDate() !== null)
		{
			return;
		}

		if (!Feature::enabled(Feature\RepeatSale::class))
		{
			(new Logger())->info('The repeat sales feature is not enabled on the portal', []);

			return;
		}

		parent::enable();

		$this->enableSystemSegments();
	}

	private function enableSystemSegments(): void
	{
		$segmentController = RepeatSaleSegmentController::getInstance();
		$segments = $segmentController->getList([
			'select' => ['*', 'ASSIGNMENT_USERS.USER_ID'],
			'filter' => [
				'!=CODE' => null,
			],
			'limit' => 0, // for all segments
		]);

		$defaultEnableSegments = SegmentManager::getDefaultEnableSegmentCodes();

		$userId = FlowController::getInstance()->getExpectedUserId();
		if ($userId <= 0)
		{
			$userId = 1;
		}

		foreach ($segments as $segment)
		{
			$segmentItem = SegmentItem::createFromEntity($segment)->setClientCoverage(null);

			if (in_array($segmentItem->getCode(), $defaultEnableSegments, true))
			{
				$segmentItem->setIsEnabled(true);
				$segmentItem->setAssignmentUserIds([$userId]);
			}

			$segmentController->update($segmentItem->getId(), $segmentItem);
		}
	}
}

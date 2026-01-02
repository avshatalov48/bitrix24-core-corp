<?php

namespace Bitrix\Crm\RepeatSale\Service\Action;

use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Crm\Item;
use Bitrix\Crm\RepeatSale\Segment\SegmentItem;
use Bitrix\Crm\RepeatSale\Segment\SystemSegmentCode;
use Bitrix\Crm\RepeatSale\Service\Context;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class CreateDealAction implements ActionInterface
{
	public function process(
		Item $clientItem,
		int $assignmentUserId,
		?Result $prevActionResult = null,
		?Context $context = null,
		?SegmentItem $segmentItem = null,
	): Result
	{
		$deal = $this->createDeal($assignmentUserId, $segmentItem);

		if ($clientItem->getEntityTypeId() === \CCrmOwnerType::Company)
		{
			$deal->setCompanyId($clientItem->getId());
		}
		else
		{
			$deal->setContactId($clientItem->getId());
		}

		$result = $this->getFactory()->getAddOperation($deal)->disableAllChecks()->launch();

		$this->sendAnalyticsEvent($segmentItem?->getCode(), $result->isSuccess());

		return $result->isSuccess() ? $result->setData(['item' => $deal]) : $result;
	}

	private function createDeal(int $assignmentUserId, ?SegmentItem $segmentItem): Item
	{
		$title = $segmentItem ? $this->getTitle($segmentItem) : null;

		return $this->getFactory()->createItem([
			'ASSIGNED_BY_ID' => $assignmentUserId,
			'TITLE' => $title ??  Loc::getMessage('CRM_REPEAT_SALE_ACTION_CREATE_DEAL_TITLE'),
			'SOURCE_ID' => 'REPEAT_SALE',
			'CATEGORY_ID' => $segmentItem->getEntityCategoryId(),
			'STAGE_ID' => $segmentItem->getEntityStageId(),
		]);
	}

	// @todo will use the field values in the future
	private function getTitle(SegmentItem $segmentItem): ?string
	{
		return $segmentItem->getEntityTitlePattern();
	}

	private function getFactory(): Factory
	{
		return Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
	}

	private function sendAnalyticsEvent(?string $segmentCode, bool $isSuccess): void
	{
		$event = new AnalyticsEvent(
			Dictionary::EVENT_ENTITY_CREATE,
			Dictionary::TOOL_CRM,
			Dictionary::CATEGORY_ENTITY_OPERATIONS,
		);

		$event
			->setSection(Dictionary::SECTION_REPEAT_SALE)
			->setSubSection(Dictionary::SUB_SECTION_REPEAT_SALE_SYSTEM)
			->setType(Dictionary::TYPE_DEAL)
			->setStatus($isSuccess ? Dictionary::STATUS_SUCCESS : Dictionary::STATUS_ERROR)
			->setP5($this->getP5BySegmentCode($segmentCode))
			->send()
		;
	}

	private function getP5BySegmentCode(string $segmentCode): ?string
	{
		return match ($segmentCode)
		{
			SystemSegmentCode::SLEEPING_CLIENT->value => 'deal-activity-less-12m',
			SystemSegmentCode::LOST_CLIENT->value => 'deal-lost-more-12m',
			SystemSegmentCode::DEAL_EVERY_YEAR->value => 'deal-annual',
			SystemSegmentCode::DEAL_EVERY_HALF_YEAR->value => 'deal-semiannual',
			SystemSegmentCode::DEAL_EVERY_MONTH->value => 'deal-month-yr',
			default => '',
		};
	}
}

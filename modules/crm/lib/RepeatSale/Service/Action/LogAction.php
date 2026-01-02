<?php

namespace Bitrix\Crm\RepeatSale\Service\Action;

use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\RepeatSale\Log\Controller\RepeatSaleLogController;
use Bitrix\Crm\RepeatSale\Log\LogItem;
use Bitrix\Crm\RepeatSale\Segment\SegmentItem;
use Bitrix\Crm\RepeatSale\Service\Context;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class LogAction implements ActionInterface
{
	private const UNKNOWN_JOB_ID = 0;
	private const UNKNOWN_SEGMENT_ID = 0;

	public function process(
		Item $clientItem,
		int $assignmentUserId,
		?Result $prevActionResult = null,
		?Context $context = null,
		?SegmentItem $segmentItem = null,
	): Result
	{
		if (!$prevActionResult?->isSuccess())
		{
			return $prevActionResult;
		}

		$item = $prevActionResult?->getData()['item'] ?? null;
		if ($item === null)
		{
			return $this->getErrorResult();
		}

		$logItem = LogItem::createFromArray([
			'jobId' => $context?->getJobId() ?? self::UNKNOWN_JOB_ID,
			'segmentId'	=> $context?->getSegmentId() ?? self::UNKNOWN_SEGMENT_ID,
			'entityTypeId' => $item->getEntityTypeId(),
			'entityId' => $item->getId(),
			'phaseSemanticId' => PhaseSemantics::PROCESS,
		]);

		return RepeatSaleLogController::getInstance()->add($logItem);
	}

	private function getErrorResult(): Result
	{
		return (new Result())->addError(new Error(Loc::getMessage('CRM_REPEAT_SALE_ACTION_LOG_ERROR')));
	}
}

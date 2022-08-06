<?php

namespace Bitrix\Crm\Integration\Bitrix24;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use CCrmOwnerType;

class FeedbackCollector
{
	public const DEAL_COUNT_ON_SLIDER_CLOSE = 3;

	/**
	 * @param Event $event
	 * @return EventResult
	 */
	public static function onFeedbackCollectorCheckCanRun(Event $event): EventResult
	{
		$canRun = false;

		$feedbackId = $event->getParameter('feedbackId');
		$userId = $event->getParameter('userId');

		if ($feedbackId === 'crmFeedbackSliderClose')
		{
			$canRun = static::checkCanRunFeedbackOnSliderClose($userId);
		}

		return new EventResult(EventResult::SUCCESS, ['canRun' => $canRun], 'crm');
	}

	private static function checkCanRunFeedbackOnSliderClose(int $userId): bool
	{
		$result =
			Container::getInstance()
				->getFactory(CCrmOwnerType::Deal)
				->getDataClass()
				::getList(
					[
						'select' => ['ID'],
						'filter' => ['=CREATED_BY_ID' => $userId],
						'limit' => static::DEAL_COUNT_ON_SLIDER_CLOSE,
					]
				)
				->fetchAll()
		;

		return (count($result) >= static::DEAL_COUNT_ON_SLIDER_CLOSE);
	}
}

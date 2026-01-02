<?php

namespace Bitrix\Crm\Agent\RepeatSale;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Controller\RepeatSale\Flow;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Crm\RepeatSale\FlowController;
use Bitrix\Crm\RepeatSale\Logger;
use Bitrix\Crm\RepeatSale\Segment\SegmentManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Main\Type\DateTime;

class FlowEnablerAgent extends AgentBase
{
	public const AGENT_DONE = false;
	public const PERIODICAL_AGENT_RUN_LATER = true;

	public static function doRun(): bool
	{
		Container::getInstance()->getContext()->setScope(Context::SCOPE_AUTOMATION);

		if (Container::getInstance()->getRepeatSaleAvailabilityChecker()->isSegmentsInitializationProgress())
		{
			(new SegmentManager())->updateFlowToPending();
		}

		$isEnabledSuccess = (new Flow())->enableAction();

		if ($isEnabledSuccess)
		{
			$event = new AnalyticsEvent(
				'rs-force-enable-flow',
				Dictionary::TOOL_CRM,
				Dictionary::CATEGORY_SYSTEM_INFORM,
			);
			$event->send();

			FlowController::getInstance()->deleteExpectedOptions();

			return self::AGENT_DONE;
		}

		(new Logger())->error('Not enabled in FlowEnablerAgent', []);

		$instance = new self();
		$instance->setExecutionPeriod(86400 * 7);

		$expectedDate = (new DateTime())->add('7 days')->disableUserTime();
		FlowController::getInstance()->saveExpectedEnableDate($expectedDate);

		return self::PERIODICAL_AGENT_RUN_LATER;
	}
}

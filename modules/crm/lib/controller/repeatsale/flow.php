<?php

namespace Bitrix\Crm\Controller\RepeatSale;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Feature;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Crm\RepeatSale\AgentsManager;
use Bitrix\Crm\RepeatSale\AvailabilityChecker;
use Bitrix\Crm\RepeatSale\FlowController;
use Bitrix\Crm\RepeatSale\Logger;
use Bitrix\Crm\RepeatSale\Queue\Controller\RepeatSaleQueueController;
use Bitrix\Crm\RepeatSale\Segment\Controller\RepeatSaleSegmentController;
use Bitrix\Crm\RepeatSale\Segment\SegmentItem;
use Bitrix\Crm\RepeatSale\Segment\SegmentManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;

final class Flow extends Base
{
	// region actions
	public function enableAction(): bool
	{
		$availabilityChecker = Container::getInstance()->getRepeatSaleAvailabilityChecker();

		if (!$this->hasPermissions($availabilityChecker))
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return false;
		}

		if (FlowController::getInstance()->getEnableDate() !== null) // flow was enabled on earlier
		{
			return false;
		}

		$this->cleanQueue();
		$this->deletePendingOption();
		$this->processSystemSegments();
		$this->removeOnlyCalcSchedulerAgent();
		$this->addAgents();
		$this->saveFlowEnableDate();

		$userId = $this->getCurrentUser()?->getId() ?? Container::getInstance()->getContext()->getUserId();

		$message = 'Flow have been enabled';
		if ($this->isScopeIsAutomation())
		{
			$message .= ' on force';
		}

		(new Logger())->info($message, ['userId' => $userId]);

		return true;
	}

	public function saveExpectedEnableDateAction(): bool
	{
		$availabilityChecker = Container::getInstance()->getRepeatSaleAvailabilityChecker();

		if (!$this->hasPermissions($availabilityChecker))
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return false;
		}

		if (!$availabilityChecker->isEnablePending()) // flow was enabled on earlier
		{
			return false;
		}

		$expectedDate = (new DateTime())->add('1 day')->disableUserTime();
		$this->saveFlowExpectedOptions($expectedDate, $this->getCurrentUser()?->getId() ?? 1);
		$this->addFlowEnablerAgent();
		$this->sendFlowEnablerAnalyticsEvent();

		return true;
	}
	// endregion

	private function hasPermissions(AvailabilityChecker $availabilityChecker): bool
	{
		return $availabilityChecker->isAvailable()
			&& $availabilityChecker->hasPermission()
			&& (
				Container::getInstance()->getUserPermissions()->repeatSale()->canEdit()
				|| $this->isScopeIsAutomation()
			)
		;
	}

	private function isScopeIsAutomation(): bool
	{
		return Container::getInstance()->getContext()->getScope() === Context::SCOPE_AUTOMATION;
	}

	private function cleanQueue(): void
	{
		$controller = RepeatSaleQueueController::getInstance();
		$controller->deleteOnlyCalcItems();
	}

	private function deletePendingOption(): void
	{
		$availabilityChecker = Container::getInstance()->getRepeatSaleAvailabilityChecker();

		Option::delete('crm', ['name' => $availabilityChecker::ENABLE_PENDING_OPTION_NAME]);
	}

	private function processSystemSegments(): void
	{
		$segmentController = RepeatSaleSegmentController::getInstance();
		$segments = $segmentController->getList([
			'select' => ['*', 'ASSIGNMENT_USERS.USER_ID'],
			'filter' => [
				'!=CODE' => null,
			],
			'limit' => 0, // for all segments
		]);

		if (Feature::enabled(Feature\RepeatSale::class))
		{
			$userId = FlowController::getInstance()->getExpectedUserId();
		}
		else
		{
			$userId = $this->getCurrentUser()?->getId() ?? Container::getInstance()->getContext()->getUserId();
		}

		if ($userId <= 0)
		{
			$userId = 1;
		}

		$defaultEnableSegments = SegmentManager::getDefaultEnableSegmentCodes();
		$isForceMode = Feature::enabled(Feature\RepeatSaleForceMode::class);

		foreach ($segments as $segment)
		{
			$segmentItem = SegmentItem::createFromEntity($segment)
				->setClientCoverage(null)
				->setAssignmentUserIds([$userId])
			;

			if (!$isForceMode && in_array($segmentItem->getCode(), $defaultEnableSegments, true))
			{
				$segmentItem->setIsEnabled(true);
			}

			$segmentController->update($segmentItem->getId(), $segmentItem);
		}
	}

	private function removeOnlyCalcSchedulerAgent(): void
	{
		AgentsManager::getInstance()->removeOnlyCalcSchedulerAgent();
	}

	private function addAgents(): void
	{
		AgentsManager::getInstance()->addAgents();
	}

	private function saveFlowEnableDate(): void
	{
		$date = (new DateTime())->disableUserTime();

		FlowController::getInstance()->saveEnableDate($date);
	}

	private function saveFlowExpectedOptions(DateTime $date, int $userId): void
	{
		$flowController = FlowController::getInstance();

		if ($flowController->getExpectedEnableDate() === null)
		{
			$flowController->saveExpectedEnableDate($date);
			$flowController->saveExpectedUserId($userId);
		}
	}

	private function addFlowEnablerAgent(): void
	{
		AgentsManager::getInstance()->addFlowEnablerAgent(24 * 60 * 60);
	}

	private function sendFlowEnablerAnalyticsEvent(): void
	{
		$event = new AnalyticsEvent(
			'rs-force-enable-flow-prepare',
			Dictionary::TOOL_CRM,
			Dictionary::CATEGORY_SYSTEM_INFORM,
		);
		$event->send();
	}
}

<?php

namespace Bitrix\Tasks\Flow\Control\Observer\Option;

use Bitrix\Tasks\Flow\Control\Observer\DeleteObserverInterface;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\Flow\Notification\NotificationService;
use Bitrix\Tasks\Flow\Option\FlowUserOption\FlowUserOptionService;
use Bitrix\Tasks\Flow\Option\OptionService;

final class DeleteObserver implements DeleteObserverInterface
{
	private OptionService $optionService;
	private NotificationService $notificationService;
	private FlowUserOptionService $flowUserOptionService;

	public function __construct()
	{
		$this->optionService = OptionService::getInstance();
		$this->notificationService = new NotificationService();
		$this->flowUserOptionService = FlowUserOptionService::getInstance();
	}

	public function update(FlowEntity $flowEntity): void
	{
		$this->optionService->deleteAll($flowEntity->getId());
		$this->notificationService->deleteConfig($flowEntity->getId());
		$this->flowUserOptionService->deleteAllForFlow($flowEntity->getId());
	}
}
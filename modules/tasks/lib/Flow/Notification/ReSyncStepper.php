<?php

namespace Bitrix\Tasks\Flow\Notification;

use Bitrix\Main\Update\Stepper;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntityCollection;
use Bitrix\Tasks\Flow\Internal\FlowTable;
use Bitrix\Tasks\Flow\Option\OptionService;
use Bitrix\Tasks\Internals\Log\LogFacade;

/**
 * ReSyncStepper is responsible for re-synchronisation flow notifications with bizprocesses or whatever integration is
 * For instance: it can solve the issue when the Loc message is changed on our side,
 * and we need to automatically let the integration side know about the change.
 * In case of bizprocess it will re-compile the robots.
 */
class ReSyncStepper extends Stepper
{
	private const LIMIT = 20;

	protected static $moduleId = 'tasks';

	private int $lastId;
	private FlowEntityCollection $flows;

	public function execute(array &$option): bool
	{
		$this
			->setLastId($option['lastId'] ?? 0)
			->fillFlowsToSync()
		;

		if ($this->flows->isEmpty())
		{
			return self::FINISH_EXECUTION;
		}

		$this
			->reSync()
			->updateLastId()
			->setOptions($option);

		return self::CONTINUE_EXECUTION;
	}

	private function fillFlowsToSync(): self
	{
		$this->flows = new FlowEntityCollection();

		try
		{
			$query = FlowTable::query();
			$query
				->setSelect(['ID'])
				->where('ID', '>', $this->lastId)
				->setLimit(self::LIMIT);
			$this->flows = $query->exec()->fetchCollection();
		}
		catch (\Exception $exception)
		{
			LogFacade::logThrowable($exception);
		}

		return $this;
	}

	private function reSync(): self
	{
		$notificationService = new NotificationService();
		$optionsService = OptionService::getInstance();
		$forceSync = true;

		foreach ($this->flows as $flow)
		{
			$notificationService->saveConfig($flow->getId(), $optionsService, $forceSync);
		}

		return $this;
	}

	private function setLastId(int $id = 0): self
	{
		$this->lastId = $id;
		return $this;
	}

	private function updateLastId(): self
	{
		$this->lastId = max(array_map(fn (FlowEntity $flow): int => $flow->getId(), iterator_to_array($this->flows)));
		return $this;
	}

	private function setOptions(array &$options): self
	{
		$options['lastId'] = $this->lastId;
		return $this;
	}
}
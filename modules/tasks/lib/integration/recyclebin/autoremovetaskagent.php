<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2022 Bitrix
 */

namespace Bitrix\Tasks\Integration\Recyclebin;

use Bitrix\Main\Loader;

class AutoRemoveTaskAgent
{
	private static bool $processing = false;
	private RecyclebinTasksRepositoryInterface $repository;

	public function __construct(RecyclebinTasksRepositoryInterface $repository)
	{
		$this->repository = $repository;
	}

	public function execute(): string
	{
		// check if recyclebin module is loaded
		if (!Loader::includeModule('recyclebin'))
		{
			return '';
		}
		// check if another agent is processing
		if ($this->isProcessing())
		{
			return $this->getAgentName();
		}

		$this->startProcessing();
		$this->repository->removeTasksFromRecycleBin(
			new TasksMaxDaysInRecycleBin(),
			new TasksMaxToRemoveFromRecycleBin()
		);
		$this->stopProcessing();

		return $this->getAgentName();
	}

	public function getAgentName(): string
	{
		// ex. (new Bitrix\Tasks\Integration\Recyclebin\AutoRemoveTaskAgent(new Bitrix\Tasks\Integration\Recyclebin\RecycleBinMemoryRepository()))->execute();
		return "(new " . self::class . "(new " . get_class($this->repository) . "()))->execute();";
	}

	public function isProcessing(): bool
	{
		return self::$processing;
	}

	private function startProcessing(): void
	{
		self::$processing = true;
	}

	private function stopProcessing(): void
	{
		if ($this->isProcessing())
		{
			self::$processing = false;
		}
	}
}

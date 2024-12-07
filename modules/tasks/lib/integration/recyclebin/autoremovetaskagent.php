<?php
/**
 * Bitrix Framework
 *
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2022 Bitrix
 */

namespace Bitrix\Tasks\Integration\Recyclebin;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Update\AgentInterface;
use Bitrix\Tasks\Update\AgentTrait;

class AutoRemoveTaskAgent implements AgentInterface
{
	use AgentTrait;

	private const OPTION_KEY = 'auto_cleaning_enabled';

	private static bool $processing = false;

	private RecyclebinTasksRepositoryInterface $repository;

	public static function execute(): string
	{
		return (new static(new RecycleBinOrmRepository()))->run();
	}

	public function __construct(RecyclebinTasksRepositoryInterface $repository)
	{
		$this->repository = $repository;
	}

	/**
	 * @uses RecycleBinOrmRepository
	 * @uses RecycleBinMemoryRepository
	 */
	public function run(): string
	{
		if (
			!Loader::includeModule('recyclebin')
			|| !$this->isEnabled()
		)
		{
			return '';
		}

		if ($this->isProcessing())
		{
			return static::getAgentName();
		}

		$this->startProcessing();
		$this->repository->removeTasksFromRecycleBin(
			new TasksMaxDaysInRecycleBin(),
			new TasksMaxToRemoveFromRecycleBin()
		);
		$this->stopProcessing();

		return static::getAgentName();
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

	private function isEnabled(): bool
	{
		return Option::get(Manager::MODULE_ID, static::OPTION_KEY, 'N') === 'Y';
	}
}

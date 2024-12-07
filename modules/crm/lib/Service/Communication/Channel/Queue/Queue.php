<?php

namespace Bitrix\Crm\Service\Communication\Channel\Queue;

use Bitrix\Crm\Service\ResponsibleQueue\Controller\QueueConfigController;
use Bitrix\Crm\Service\ResponsibleQueue\Entity\QueueTable;
use Bitrix\Main\ObjectNotFoundException;

final class Queue
{
	private array $config = [];

	/**
	 * @throws ObjectNotFoundException
	 */
	public function __construct(readonly private int $queueConfigId)
	{
		$this->initQueue();
	}

	public function getNextUserId(int $currentUserId = 0): ?int
	{
		$user = QueueTable::getList([
			'select' => ['USER_ID'],
			'filter' => [
				'=CONFIG_ID' => $this->config['ID'],
				'!=USER_ID' => $currentUserId,
			],
			'order' => [
				'SORT' => 'ASC',
				'ID' => 'ASC',
			],
			'limit' => 1,
		])->fetch();

		return $user['USER_ID'] ?? null;
	}

	public function isForwardToEnabled(): bool
	{
		return isset($this->config['SETTINGS'][QueueConfig::CFG_FORWARD_TO])
			&& $this->config['SETTINGS'][QueueConfig::CFG_FORWARD_TO] === "true"
		;
	}

	public function isTimeTrackingEnabled(): bool
	{
		return isset($this->config['SETTINGS'][QueueConfig::CFG_TIME_TRACKING])
			&& $this->config['SETTINGS'][QueueConfig::CFG_TIME_TRACKING]
		;
	}

	private function initQueue(): void
	{
		$this->config = QueueConfigController::getInstance()->get($this->queueConfigId);
		if (!$this->config)
		{
			throw new \Bitrix\Main\ObjectNotFoundException(
				'Queue configuration with ID ' . $this->queueConfigId . 'not found'
			);
		}

	}
}

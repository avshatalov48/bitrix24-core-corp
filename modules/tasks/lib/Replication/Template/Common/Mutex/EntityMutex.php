<?php

namespace Bitrix\Tasks\Replication\Template\Common\Mutex;

use Bitrix\Tasks\Replication\AbstractMutex;

class EntityMutex extends AbstractMutex
{
	private int $entityId;

	public function __construct(int $entityId, bool $isEnabled = true)
	{
		parent::__construct($isEnabled);
		$this->entityId = $entityId;
	}

	protected function getTTL(): int
	{
		return 5;
	}

	protected function getCacheName(): string
	{
		return 'tasks_agent_entity_mutex_' . $this->entityId;
	}
}
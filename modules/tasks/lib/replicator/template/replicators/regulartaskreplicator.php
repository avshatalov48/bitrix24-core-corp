<?php

namespace Bitrix\Tasks\Replicator\Template\Replicators;

use Bitrix\Main\Config\Option;
use Bitrix\Tasks\Replicator\Checker;
use Bitrix\Tasks\Replicator\Producer;
use Bitrix\Tasks\Replicator\Repeater;
use Bitrix\Tasks\Replicator\Replicator;
use Bitrix\Tasks\Replicator\Template\Repository;
use Bitrix\Tasks\Replicator\Template\TaskProducer;
use Bitrix\Tasks\Replicator\Template\TaskRepeater;
use Bitrix\Tasks\Replicator\Template\TaskReplicationChecker;

class RegularTaskReplicator extends Replicator
{
	public static function isEnabled(): bool
	{
		return Option::get('tasks', static::NEW_REPLICATION_KEY, 'N') === 'Y';
	}

	protected function getProducer(): Producer
	{
		return new TaskProducer($this->getRepository());
	}

	protected function getRepeater(): Repeater
	{
		return new TaskRepeater($this->getRepository());
	}

	protected function getChecker(): Checker
	{
		return new TaskReplicationChecker($this->getRepository());
	}

	protected function getRepository(): Repository
	{
		return new Repository\TemplateRepository($this->templateId);
	}
}
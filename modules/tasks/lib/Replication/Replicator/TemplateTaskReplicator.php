<?php

namespace Bitrix\Tasks\Replication\Replicator;

use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Replication\AbstractReplicator;
use Bitrix\Tasks\Replication\CheckerInterface;
use Bitrix\Tasks\Replication\ProducerInterface;
use Bitrix\Tasks\Replication\RepeaterInterface;
use Bitrix\Tasks\Replication\ReplicationResult;
use Bitrix\Tasks\Replication\Template\Common\TemplateTaskChecker;
use Bitrix\Tasks\Replication\Fake\FakeRepeater;
use Bitrix\Tasks\Replication\Template\Common\TemplateTaskProducer;
use Bitrix\Tasks\Replication\Template\Repetition\RegularTemplateTaskProducer;
use Bitrix\Tasks\Replication\Repository\TemplateRepository;
use Bitrix\Tasks\Replication\RepositoryInterface;

class TemplateTaskReplicator extends AbstractReplicator
{
	private int $parentTaskId = 0;
	public static function isEnabled(): bool
	{
		return true;
	}

	public static function getPayloadKey(): string
	{
		return '';
	}

	public function setParentTaskId(int $taskId): static
	{
		$this->parentTaskId = $taskId;
		return $this;
	}

	protected function getProducer(): ProducerInterface
	{
		return new TemplateTaskProducer($this->getRepository());
	}


	protected function getRepeater(): RepeaterInterface
	{
		return new FakeRepeater($this->getRepository());
	}

	protected function getChecker(): CheckerInterface
	{
		return new TemplateTaskChecker($this->getRepository());
	}

	protected function getRepository(): RepositoryInterface
	{
		return new TemplateRepository($this->entityId);
	}

	protected function replicateImplementation(int $entityId, bool $force = false): ReplicationResult
	{
		$this->currentResults = [];
		$this->replicationResult = new ReplicationResult($this);
		if (!static::isEnabled())
		{
			return $this->replicationResult;
		}

		$this->init($entityId);

		if ($this->checker->stopReplicationByInvalidData())
		{
			return $this->replicationResult;
		}

		$tree = $this->buildTree()[$this->entityId] ?? [];
		foreach ($tree as $templateId)
		{
			/** @var RepositoryInterface $repositoryClass */
			$repositoryClass = $this->getRepositoryClass();
			/** @var ProducerInterface $producerClass */
			$producerClass = $this->getProducerClass();
			$this->currentResults[] = (new $producerClass(new $repositoryClass($templateId)))
				->setCreatedDate(new DateTime())
				->setParentTaskId($this->parentTaskId)
				->produceTask();
		}

		return $this->replicationResult
			->merge(...$this->currentResults)
			->writeToLog();
	}

	private function buildTree(): array
	{
		$children = $this->getRepository()->getEntity()->getChildren();
		if (empty($children))
		{
			return [];
		}

		$walkQueue = [$this->getRepository()->getEntity()->getId()];
		$treeBundles = [];

		foreach ($children as $subTemplate)
		{
			$treeBundles[$subTemplate['BASE_TEMPLATE_ID']][] = $subTemplate['ID'];
		}

		$tree = $treeBundles;
		$met = [];
		while (!empty($walkQueue))
		{
			$topTemplate = array_shift($walkQueue);
			if (isset($met[$topTemplate])) // hey, i`ve met this guy before!
			{
				return [];
			}
			$met[$topTemplate] = true;

			if (is_array($treeBundles[$topTemplate] ?? null))
			{
				foreach ($treeBundles[$topTemplate] as $template)
				{
					$walkQueue[] = $template;
				}
			}
			unset($treeBundles[$topTemplate]);
		}

		return $tree;
	}

	private function getProducerClass(): string
	{
		return $this->getProducer()::class;
	}

	private function getRepositoryClass(): string
	{
		return $this->getRepository()::class;
	}
}
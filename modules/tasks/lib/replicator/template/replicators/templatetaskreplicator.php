<?php

namespace Bitrix\Tasks\Replicator\Template\Replicators;

use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Replicator\AbstractReplicator;
use Bitrix\Tasks\Replicator\CheckerInterface;
use Bitrix\Tasks\Replicator\ProducerInterface;
use Bitrix\Tasks\Replicator\RepeaterInterface;
use Bitrix\Tasks\Replicator\ReplicationResult;
use Bitrix\Tasks\Replicator\Template\common\TemplateTaskChecker;
use Bitrix\Tasks\Replicator\Template\Repetition\RegularTemplateTaskProducer;
use Bitrix\Tasks\Replicator\Template\Repository\TemplateRepository;
use Bitrix\Tasks\Replicator\Template\RepositoryInterface;

class TemplateTaskReplicator extends AbstractReplicator
{
	private int $parentTaskId = 0;
	public static function isEnabled(): bool
	{
		return RegularTemplateTaskReplicator::isEnabled();
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
		return new RegularTemplateTaskProducer($this->getRepository());
	}


	protected function getRepeater(): RepeaterInterface
	{
		return new class($this->getRepository()) implements RepeaterInterface {
			public function __construct(RepositoryInterface $repository) {}
			public function setAdditionalData($data): void {}
			public function getAdditionalData() {}
			public function repeatTask(): Result { return new Result(); }
			public function isDebug(): bool { return false; }
		};
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
				->setParentId($this->parentTaskId)
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
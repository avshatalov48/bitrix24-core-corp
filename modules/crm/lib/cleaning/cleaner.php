<?php

namespace Bitrix\Crm\Cleaning;

use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\Cleaning\Cleaner\Job;
use Bitrix\Crm\EntityPermsTable;
use Bitrix\Crm\EventRelationsTable;
use Bitrix\Crm\Integration;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Kanban;
use Bitrix\Crm\Ml;
use Bitrix\Crm\Model\FieldContentTypeTable;
use Bitrix\Crm\ProductRowTable;
use Bitrix\Crm\Pseudoactivity;
use Bitrix\Crm\Relation;
use Bitrix\Crm\Requisite;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Crm\Tracking;
use Bitrix\Crm\UtmTable;
use Bitrix\Main\Result;

final class Cleaner
{
	/** @var Cleaner\Options */
	private $options;
	/** @var Cleaner\Job[] */
	private $jobs = [];

	public function __construct(Cleaner\Options $options)
	{
		$this->options = $options;
	}

	public function cleanup(): Result
	{
		/*
		 * be aware that this method can run even after dynamic type deletion
		 * this means that there is no factory for such entityTypeId and \CCrmOwnerType::IsDefined(entityTypeId) returns false
		 * in most cases, if you just delete something by filter =ENTITY_TYPE_ID, you will be fine
		 * but remember this note and make sure that code that you place here handles this case correctly
		 */

		(new \CCrmFieldMulti())->DeleteByElement(
			\CCrmOwnerType::ResolveName($this->getEntityTypeId()),
			$this->getEntityId()
		);
		ProductRowTable::deleteByItem($this->getEntityTypeId(), $this->getEntityId());
		Kanban\SortTable::clearEntity($this->getEntityId(), \CCrmOwnerType::ResolveName($this->getEntityTypeId()));
		EntityPermsTable::clearByEntity(\CCrmOwnerType::ResolveName($this->getEntityTypeId()), $this->getEntityId());
		TimelineEntry::deleteByOwner($this->getEntityTypeId(), $this->getEntityId());
		EventRelationsTable::deleteByItem($this->getEntityTypeId(), $this->getEntityId());
		\CCrmActivity::DeleteByOwner($this->getEntityTypeId(), $this->getEntityId());
		Requisite\EntityLink::unregister($this->getEntityTypeId(), $this->getEntityId());
		UtmTable::deleteEntityUtm($this->getEntityTypeId(), $this->getEntityId());
		Tracking\Entity::deleteTrace($this->getEntityTypeId(), $this->getEntityId());
		Ml\Scoring::onEntityDelete($this->getEntityTypeId(), $this->getEntityId());
		Relation\EntityRelationTable::deleteByItem($this->getEntityTypeId(), $this->getEntityId());
		Pseudoactivity\WaitEntry::deleteByOwner($this->getEntityTypeId(), $this->getEntityId());
		Integration\Im\Chat::deleteChat([
			'ENTITY_TYPE_ID' => $this->getEntityTypeId(),
			'ENTITY_ID' => $this->getEntityId(),
		]);
		Badge::deleteByEntity(new ItemIdentifier($this->getEntityTypeId(), $this->getEntityId()));
		FieldContentTypeTable::deleteByItem(new ItemIdentifier($this->getEntityTypeId(), $this->getEntityId()));

		return $this->runJobs();
	}

	private function getEntityTypeId(): int
	{
		return $this->options->getEntityTypeId();
	}

	private function getEntityId(): int
	{
		return $this->options->getEntityId();
	}

	private function runJobs(): Result
	{
		$result = new Result();

		foreach ($this->jobs as $job)
		{
			$jobResult = $job->run($this->options);
			if (!$jobResult->isSuccess())
			{
				$result->addErrors($jobResult->getErrors());
			}
		}

		return $result;
	}

	public function addJob(Job $job): self
	{
		$this->jobs[] = $job;

		return $this;
	}

	public function getOptions(): Cleaner\Options
	{
		return $this->options;
	}
}

<?php

namespace Bitrix\Tasks\Replication;

use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Replication\RepositoryInterface;

interface ProducerInterface
{
	public function __construct(RepositoryInterface $repository);
	public function produceTask(): Result;
	public function setParentTaskId(int $taskId): static;
	public function setCreatedDate(DateTime $createdDate): static;
}
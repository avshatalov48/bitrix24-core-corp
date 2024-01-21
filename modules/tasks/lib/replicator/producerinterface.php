<?php

namespace Bitrix\Tasks\Replicator;

use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Replicator\Template\RepositoryInterface;

interface ProducerInterface
{
	public function __construct(RepositoryInterface $repository);
	public function produceTask(): Result;
	public function setCreatedDate(DateTime $createdDate): static;
}
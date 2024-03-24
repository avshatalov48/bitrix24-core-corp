<?php

namespace Bitrix\Tasks\Replication;

use Bitrix\Main\Result;
use Bitrix\Tasks\Replication\RepositoryInterface;

interface RepeaterInterface
{
	public function __construct(RepositoryInterface $repository);
	public function repeatTask(): Result;
	public function isDebug(): bool;
	public function setAdditionalData($data): void;
	public function getAdditionalData();
}
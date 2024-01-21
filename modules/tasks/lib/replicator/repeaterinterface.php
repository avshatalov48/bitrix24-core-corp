<?php

namespace Bitrix\Tasks\Replicator;

use Bitrix\Main\Result;
use Bitrix\Tasks\Replicator\Template\RepositoryInterface;

interface RepeaterInterface
{
	public function __construct(RepositoryInterface $repository);
	public function repeatTask(): Result;
	public function isDebug(): bool;
	public function setAdditionalData($data): void;
	public function getAdditionalData();
}
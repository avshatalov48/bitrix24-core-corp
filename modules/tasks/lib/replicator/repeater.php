<?php

namespace Bitrix\Tasks\Replicator;

use Bitrix\Main\Result;
use Bitrix\Tasks\Replicator\Template\Repository;

interface Repeater
{
	public function __construct(Repository $repository);
	public function repeatTask(): Result;
	public function isDebug(): bool;
}
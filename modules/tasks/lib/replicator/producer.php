<?php

namespace Bitrix\Tasks\Replicator;

use Bitrix\Main\Result;
use Bitrix\Tasks\Replicator\Template\Repository;

interface Producer
{
	public function __construct(Repository $repository);
	public function produceTask(): Result;
}
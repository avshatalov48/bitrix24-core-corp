<?php

namespace Bitrix\Tasks\Internals\Task\Search;

use Bitrix\Tasks\Internals\Task\Search\Exception\SearchIndexException;

interface RepositoryInterface
{
	/** @throws SearchIndexException */
	public function getTask(): array;
}
<?php

namespace Bitrix\Tasks\Replication;

use Bitrix\Tasks\Internals\Task\Template\TemplateObject;
use Bitrix\Tasks\Internals\TaskObject;

interface RepositoryInterface
{
	public function getEntity(): null|TemplateObject|TaskObject;
	public function inject(TemplateObject|TaskObject $object): static;
	public function drop(): void;
}
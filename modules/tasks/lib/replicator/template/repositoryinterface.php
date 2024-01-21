<?php

namespace Bitrix\Tasks\Replicator\Template;

use Bitrix\Tasks\Internals\Task\Template\TemplateObject;
use Bitrix\Tasks\Internals\TaskObject;

interface RepositoryInterface
{
	public function getEntity(): null|TemplateObject|TaskObject;
	public function inject(TemplateObject|TaskObject $object): void;
	public function drop(): void;
}
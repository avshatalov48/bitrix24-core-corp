<?php

namespace Bitrix\Tasks\Replicator\Template;

use Bitrix\Tasks\Internals\Task\Template\TemplateObject;

interface Repository
{
	public function getTemplate(): ?TemplateObject;
}
<?php

namespace Bitrix\Tasks\Replication\Template\Repetition;

use Bitrix\Tasks\Replication\Template\Common\TemplateTaskProducer;
use Bitrix\Tasks\Replication\Template\Repetition\Service\TemplateHistoryService;

class RegularTemplateTaskProducer extends TemplateTaskProducer
{
	protected function initHistoryService(): void
	{
		$this->templateHistoryService = new TemplateHistoryService($this->repository);
	}
}

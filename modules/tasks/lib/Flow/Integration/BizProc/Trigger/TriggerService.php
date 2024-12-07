<?php

namespace Bitrix\Tasks\Flow\Integration\BizProc\Trigger;

use Bitrix\Bizproc\Automation\Target\BaseTarget;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Flow\Integration\BizProc\DocumentTrait;
use Bitrix\Tasks\Integration\Bizproc\Automation\Target\ProjectTask;

class TriggerService
{
	use DocumentTrait;

	protected BaseTarget $target;

	protected int $projectId;
	protected int $stageId;

	private bool $isAvailable;

	final public function __construct(int $stageId, int $projectId)
	{
		if (!$this->isAvailable())
		{
			return;
		}

		$this->target = new ProjectTask();
		$this->target->setDocumentType($this->getDocumentType($projectId));

		$this->stageId = $stageId;

	}

	public function add(TriggerCommand ...$commands): void
	{
		if (!$this->isAvailable)
		{
			return;
		}

		$triggers = [];
		foreach ($commands as $command)
		{
			$trigger = $command->toArray();
			$trigger['DOCUMENT_STATUS'] = $this->stageId;

			$triggers[] = $trigger;
		}

		if ([] !== $triggers)
		{
			$this->target->setTriggers($triggers);
		}
	}

	private function isAvailable(): bool
	{
		$this->isAvailable ??= Loader::includeModule('bizproc');
		return $this->isAvailable;
	}
}
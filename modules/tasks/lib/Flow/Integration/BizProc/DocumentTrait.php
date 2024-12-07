<?php

namespace Bitrix\Tasks\Flow\Integration\BizProc;

use Bitrix\Bizproc\Automation\Engine\Template;
use Bitrix\Tasks\Integration\Bizproc\Document\Task;

trait DocumentTrait
{
	protected static array $templates = [];

	public function getDocumentType(int $projectId): array
	{
		return ['tasks', Task::class, Task::resolveProjectTaskType($projectId)];
	}

	protected function createTemplate(int $stageId, int $projectId): Template
	{
		$documentType = $this->getDocumentType($projectId);

		$key = implode('_', $documentType) . '_ ' . $stageId;
		if (!isset(static::$templates[$key]))
		{
			static::$templates[$key] = new Template($documentType, $stageId);
		}

		return static::$templates[$key];
	}
}
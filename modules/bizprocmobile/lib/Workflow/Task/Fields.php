<?php

namespace Bitrix\BizprocMobile\Workflow\Task;

use Bitrix\Bizproc\FieldType;
use Bitrix\BizprocMobile\EntityEditor\Converter;
use Bitrix\Main\Loader;
use Bitrix\UI\FileUploader\PendingFile;

Loader::requireModule('ui');
Loader::requireModule('bizproc');

class Fields
{
	/** @var PendingFile[] */
	private array $pendingFiles = [];
	private int $taskId;

	public function __construct(int $taskId)
	{
		$this->taskId = $taskId;
	}

	public function extract(array $fields, int $userId = 0): array
	{
		$task = \CBPTaskService::getList(
			arFilter: ['ID' => $this->taskId],
			arSelectFields: ['ID', 'ACTIVITY', 'PARAMETERS', 'STATUS']
		)->fetch();

		if ($task && (int)$task['STATUS'] === \CBPTaskStatus::Running)
		{
			$taskFields = \CBPDocument::getTaskControls($task, $userId)['FIELDS'] ?? [];
			$documentId =
				is_array($task['PARAMETERS']['DOCUMENT_ID'] ?? null)
					? $task['PARAMETERS']['DOCUMENT_ID']
					: null
			;

			$converter =
				(new Converter($taskFields, $documentId, $fields))
					->setContext(Converter::CONTEXT_TASK, ['taskId' => $this->taskId])
					->toWeb()
			;

			$this->pendingFiles = $converter->getPendingFiles();
			$fields = $converter->getConvertedValues();
		}

		return $fields;
	}

	public function savePendingFiles(): void
	{
		foreach ($this->pendingFiles as $pendingFile)
		{
			$pendingFile->makePersistent();
		}

		$this->pendingFiles = [];
	}
}

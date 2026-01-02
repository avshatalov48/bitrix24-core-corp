<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Attachment;

use Bitrix\Tasks\V2\Internal\Integration\Disk\Service\Task\AttachmentService;

class AttachFilesHandler
{
	public function __construct(
		private readonly AttachmentService $attachmentService,
	)
	{

	}

	public function __invoke(AttachFilesCommand $command): void
	{
		$this->attachmentService->add(
			taskId: $command->taskId,
			userId: $command->userId,
			fileIds: $command->fileIds,
		);
	}
}

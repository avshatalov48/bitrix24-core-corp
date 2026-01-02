<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Attachment;

use Bitrix\Tasks\V2\Internal\Integration\Disk\Service\Task\AttachmentService;

class DetachFilesHandler
{
	public function __construct(
		private readonly AttachmentService $attachmentService,
	) {}

	public function __invoke(DetachFilesCommand $command): void
	{
		$this->attachmentService->delete(
			taskId: $command->taskId,
			userId: $command->userId,
			fileIds: $command->fileIds,
		);
	}
}


<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Attachment;

use Bitrix\Main\Error;
use Bitrix\Main\Validation\Rule\PositiveNumber;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Integration\Disk\Validation\Rule\NewFiles;
use Bitrix\Tasks\V2\Internal\Result\Result;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Exception;

class AttachFilesCommand extends AbstractCommand
{
	public function __construct(
		#[PositiveNumber]
		public readonly int $taskId,
		#[PositiveNumber]
		public readonly int $userId,
		#[NewFiles]
		public readonly array $fileIds,
	)
	{

	}

	protected function executeInternal(): Result
	{
		$result = new Result();

		$attachmentService = Container::getInstance()->getAttachmentService();

		$handler = new AttachFilesHandler($attachmentService);

		try
		{
			$handler($this);

			return $result;
		}
		catch (Exception $e)
		{
			return $result->addError(Error::createFromThrowable($e));
		}
	}
}

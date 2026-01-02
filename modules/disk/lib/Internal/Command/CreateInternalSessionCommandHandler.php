<?php
declare(strict_types=1);

namespace Bitrix\Disk\Internal\Command;

use Bitrix\Disk\Document\DocumentSessionResult;
use Bitrix\Disk\Document\Models\DocumentService;
use Bitrix\Disk\Document\Models\DocumentSession;
use Bitrix\Disk\Document\Models\DocumentSessionContext;
use Bitrix\Disk\File;
use Bitrix\Disk\TypeFile;
use Bitrix\Main\Application;
use Bitrix\Main\Diag\ExceptionHandler;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;

class CreateInternalSessionCommandHandler
{
	private ExceptionHandler $exceptionHandler;

	public function __construct()
	{
		$this->exceptionHandler = Application::getInstance()->getExceptionHandler();
	}

	public function __invoke(CreateInternalSessionCommand $command): DocumentSessionResult
	{
		$result = new DocumentSessionResult();

		if (!$command->documentSource->hasValidFile())
		{
			$result->addError(new Error('File not found in source.'));

			return $result;
		}

		/** @var File $fileFromSource */
		$fileFromSource = $command->documentSource->getFileFromSource();

		$documentSessionContext = DocumentSessionContext::tryBuildByAttachedObject(
			$command->documentSource->getAttachedObject(),
			$fileFromSource,
		);

		$command->sessionManager
			->setUserId((int)CurrentUser::get()->getId())
			->setSessionType($command->type)
			->setService($this->getSessionServiceByFile($fileFromSource))
			->setSessionContext($documentSessionContext)
			->setFile($command->documentSource->getFile())
			->setVersion($command->documentSource->getVersion())
			->setAttachedObject($command->documentSource->getAttachedObject())
		;

		if (!$command->sessionManager->lock())
		{
			$result->addError(new Error('Could not getting lock for the session.'));

			return $result;
		}

		try
		{
			$documentSession = $command->sessionManager->findOrCreateSession($command->exactUser);

			if ($documentSession instanceof DocumentSession)
			{
				$result->setDocumentSession($documentSession);
			}
			else
			{
				$result->addError(new Error('Could not getting document session.'));
			}
		}
		catch (\Throwable $e)
		{
			$this->exceptionHandler->writeToLog($e);

			$result->addError(new Error($e->getMessage(), $e->getCode()));
		}
		finally
		{
			$command->sessionManager->unlock();
		}

		return $result;
	}

	private function getSessionServiceByFile(File $file): DocumentService
	{
		$typeFile = (int)$file->getTypeFile();

		return match ($typeFile)
		{
			TypeFile::FLIPCHART => DocumentService::FlipChart,
			default => DocumentService::OnlyOffice,
		};
	}
}

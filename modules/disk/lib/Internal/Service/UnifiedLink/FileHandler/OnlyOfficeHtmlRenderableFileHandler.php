<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internal\Service\UnifiedLink\FileHandler;

use Bitrix\Disk\Document\DocumentSessionResult;
use Bitrix\Disk\Document\DocumentSource;
use Bitrix\Disk\Document\Flipchart\SessionManager;
use Bitrix\Disk\Document\Models\DocumentSession;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\TrackedObjectManager;
use Bitrix\Disk\Internal\Service\SessionCommandFactory;
use Bitrix\Main\Application;
use Bitrix\Main\Command\Exception\CommandException;
use Bitrix\Main\Command\Exception\CommandValidationException;
use Bitrix\Main\Diag\ExceptionHandler;
use Bitrix\Main\Engine\CurrentUser;

class OnlyOfficeHtmlRenderableFileHandler implements HtmlRenderableFileHandler
{
	private TrackedObjectManager $trackedObjectManager;
	private SessionCommandFactory $sessionCommandFactory;
	private ExceptionHandler $exceptionHandler;

	public function __construct(
		private readonly File $file,
		private readonly DocumentSource $documentSource,
	) {
		$this->trackedObjectManager = Driver::getInstance()->getTrackedObjectManager();

		$this->sessionCommandFactory = new SessionCommandFactory($documentSource, new SessionManager());
		$this->exceptionHandler = Application::getInstance()->getExceptionHandler();
	}

	public function view(): FileHandlerOperationResult
	{
		return $this->handle(DocumentSession::TYPE_VIEW);
	}

	public function edit(): FileHandlerOperationResult
	{
		return $this->handle(DocumentSession::TYPE_EDIT);
	}

	private function handle(int $type): FileHandlerOperationResult
	{
		$createInternalSessionCommand = $this->sessionCommandFactory->createCreateInternalSessionCommand($type);

		try
		{
			/** @var DocumentSessionResult $sessionCreationResult */
			$sessionCreationResult = $createInternalSessionCommand->run();
		}
		catch (CommandException|CommandValidationException $e)
		{
			$this->exceptionHandler->writeToLog($e);

			return FileHandlerOperationResult::createFromException($e);
		}

		if (!$sessionCreationResult->isSuccess())
		{
			return FileHandlerOperationResult::createError($sessionCreationResult->getErrorCollection());
		}

		/** @var DocumentSession $documentSession */
		$documentSession = $sessionCreationResult->getDocumentSession();

		if ($documentSession->canUserRead($this->getCurrentUser()))
		{
			$this->trackObject();
		}

		return $this->showEditor($documentSession);
	}

	private function trackObject(): void
	{
		$userId = $this->getCurrentUser()->getId();
		if ($this->documentSource->getAttachedObject() !== null)
		{
			$this->trackedObjectManager->pushAttachedObject($userId, $this->documentSource->getAttachedObject(), true);
		}
		else
		{
			$this->trackedObjectManager->pushFile($userId, $this->file, true);
		}
	}

	private function showEditor(DocumentSession $documentSession): FileHandlerOperationResult
	{
		$content = $GLOBALS['APPLICATION']->includeComponent(
			'bitrix:ui.sidepanel.wrapper',
			'',
			[
				'RETURN_CONTENT' => true,
				'POPUP_COMPONENT_NAME' => 'bitrix:disk.file.editor-onlyoffice',
				'POPUP_COMPONENT_TEMPLATE_NAME' => '',
				'POPUP_COMPONENT_PARAMS' => [
					'DOCUMENT_SESSION' => $documentSession,
					'SHOW_BUTTON_OPEN_NEW_WINDOW' => false,
					'UNIFIED_LINK_MODE' => true,
					'UNIFIED_LINK_ACCESS_ONLY' => !$documentSession->canUserRead($this->getCurrentUser()),
					'FILE_UNIQUE_CODE' => $documentSession->getFile()?->getUniqueCode(),
				],
				'PLAIN_VIEW' => true,
				'IFRAME_MODE' => true,
				'PREVENT_LOADING_WITHOUT_IFRAME' => false,
				'USE_PADDING' => false,
			],
		);

		return FileHandlerOperationResult::createSuccess($content);
	}

	private function getCurrentUser(): CurrentUser
	{
		return CurrentUser::get();
	}
}

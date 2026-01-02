<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internal\Service\UnifiedLink\FileHandler;

use Bitrix\Disk\Controller\Integration\Flipchart;
use Bitrix\Disk\Document\DocumentSource;
use Bitrix\Disk\Document\Flipchart\Configuration;
use Bitrix\Disk\Document\Flipchart\SessionManager;
use Bitrix\Disk\Document\Models\DocumentSession;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Internal\Service\SessionCommandFactory;
use Bitrix\Disk\TrackedObjectManager;
use Bitrix\Main\Application;
use Bitrix\Main\Command\Exception\CommandException;
use Bitrix\Main\Command\Exception\CommandValidationException;
use Bitrix\Main\Diag\ExceptionHandler;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Web\Uri;

class BoardHtmlRenderableFileHandler implements HtmlRenderableFileHandler
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
		$createInternalSessionCommand = $this->sessionCommandFactory->createCreateInternalSessionCommand($type, true);

		try
		{
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
		$downloadUrl = (new Flipchart())->getActionUri('getDocument',
			[
				'sessionId' => $documentSession->getExternalHash(),
				'userId' => $documentSession->getUserId(),
			],
			true,
		);

		if (Configuration::isForceHttpForDocumentUrl())
		{
			$downloadUrl = $downloadUrl->withScheme('http');
		}

		$avatarUri = (new Uri($documentSession->getUser()->getAvatarSrc()))->toAbsolute();

		$content = $GLOBALS['APPLICATION']->includeComponent(
			'bitrix:ui.sidepanel.wrapper',
			'',
			[
				'POPUP_COMPONENT_NAME' => 'bitrix:disk.flipchart.editor',
				'POPUP_COMPONENT_PARAMS' => [
					'DOCUMENT_SESSION' => $documentSession,
					'DOCUMENT_URL' => $downloadUrl,
					'STORAGE_ID' => $this->file->getStorageId(),
					'USER_ID' => (string)$documentSession->getUserId(),
					'USERNAME' => $documentSession->getUser()->getFormattedName(),
					'AVATAR_URL' => $avatarUri,
					'CAN_EDIT_BOARD' => true,
					'SHOW_TEMPLATES_MODAL' => false,
					'EXTERNAL_LINK_MODE' => false,
					'UNIFIED_LINK_ACCESS_ONLY' => !$documentSession->canUserRead(CurrentUser::get()),
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

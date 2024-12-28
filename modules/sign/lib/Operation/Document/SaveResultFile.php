<?php

namespace Bitrix\Sign\Operation\Document;

use Bitrix\Disk\Ui\Text;
use Bitrix\Main;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Loader;
use Bitrix\Sign\Contract;
use Bitrix\Sign\File;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Integration\Im\ImService;
use Bitrix\Sign\Service\Sign\DocumentService;
use Bitrix\Sign\Service\UserService;
use Bitrix\Sign\Type\DocumentScenario;

final class SaveResultFile implements Contract\Operation
{
	private readonly DocumentService $documentService;
	private readonly ImService $imService;
	private readonly UserService $userService;

	public function __construct(
		private readonly string $documentUid,
		private readonly array $file,
	)
	{
		$container = Container::instance();
		$this->documentService = $container->getDocumentService();
		$this->imService = $container->getImService();
		$this->userService = $container->getUserService();
	}

	public function launch(): Main\Result
	{
		$result = new Main\Result();

		if (!$this->documentUid)
		{
			return $result->addError(new Main\Error('Invalid documentUid.'));
		}

		$document = $this->documentService->getByUid($this->documentUid);
		if ($document === null)
		{
			return $result->addError(new Main\Error('Document not found.'));
		}

		if (empty($this->file))
		{
			return $result->addError(new Main\Error('Invalid file data.'));
		}

		$file = $this->file;
		if ($this->isDocumentInitiatedFromGroupChat($document))
		{
			$ext = Path::getExtension((string)($file['name'] ?? ''));
			if ($ext !== 'pdf')
			{
				return $result->addError(new Main\Error('Invalid file extension.'));
			}

			$fileName = $this->preparePdfFileName($document);
			if ($fileName)
			{
				$file['name'] = $fileName;
			}
		}

		$file = new File($file);
		$fileId = $file->save();
		if ($fileId === null)
		{
			return $result->addError(new Main\Error('Can\'t save file.'));
		}

		$setResultFileIdResult = $this->documentService->setResultFileId($document, $fileId);

		if (!$setResultFileIdResult->isSuccess())
		{
			return $setResultFileIdResult;
		}

		if ($this->isDocumentInitiatedFromGroupChat($document))
		{
			$sendFileResult = $this->sendB2bSignedFileToGroupChat($document);
			if (!$sendFileResult->isSuccess())
			{
				return $sendFileResult;
			}
		}

		return $result;
	}

	private function preparePdfFileName(Document $document): string
	{
		$fileBaseName = str_replace('.', '-', $this->documentService->getComposedTitleByDocument($document));
		$fileName = sprintf('%s.pdf', $fileBaseName);
		if (Loader::includeModule('disk'))
		{
			$fileName = Text::correctFilename($fileName);
		}

		if (!Path::validateFilename($fileName))
		{
			return '';
		}

		return $fileName;
	}

	private function isDocumentInitiatedFromGroupChat(Document $document): bool
	{
		return DocumentScenario::isB2BScenario($document->scenario ?? '') && $document->chatId !== null;
	}

	private function sendB2bSignedFileToGroupChat(Document $document): Main\Result
	{
		if (!DocumentScenario::isB2BScenario($document->scenario ?? ''))
		{
			return (new Main\Result())->addError(new Main\Error('Invalid document scenario.'));
		}

		if ($document->chatId === null)
		{
			return (new Main\Result())->addError(new Main\Error('Invalid chatId.'));
		}

		if ($document->resultFileId === null)
		{
			return (new Main\Result())->addError(new Main\Error('Invalid resultFileId.'));
		}

		if ($document->createdById === null)
		{
			return (new Main\Result())->addError(new Main\Error('Invalid createById.'));
		}

		$user = $this->userService->getUserById($document->createdById);

		if ($user === null)
		{
			return (new Main\Result())->addError(new Main\Error('User not found.'));
		}

		return $this->imService->sendBFileToGroupChat(
			$document->resultFileId,
			$document->chatId,
			$document->createdById,
		);
	}
}
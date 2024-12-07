<?php

namespace Bitrix\Sign\Service\Sign;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\AddResult;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Rpa\Command\Add;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Item\Blank;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Fs\File;
use Bitrix\Sign\Item\Fs\FileCollection;
use Bitrix\Sign\Item\Fs\FileContent;
use Bitrix\Sign\Repository\BlankRepository;
use Bitrix\Sign\Repository\FileRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\BlankScenario;
use Bitrix\Sign\Upload\BlankUploadController;

class BlankService
{
	private BlankFileService $blankFileService;
	private BlankRepository $blankRepository;
	private FileRepository $fileRepository;

	public function __construct(
		?BlankFileService $blankFileService = null,
		?BlankRepository $blankRepository = null,
		?FileRepository $fileRepository = null,
	)
	{
		$this->blankFileService = $blankFileService ?? Container::instance()
			->getSignBlankFileService();
		$this->blankRepository = $blankRepository ?? Container::instance()
			->getBlankRepository();
		$this->fileRepository = $fileRepository ?? Container::instance()
			->getFileRepository();
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function createFromFile(FileCollection $fileCollection, string $scenario = BlankScenario::B2B, bool $forTemplate = false): AddResult
	{
		if (!in_array($scenario, BlankScenario::getAll(), true))
		{
			return (new AddResult())->addError(new Error('Wrong blank scenario'));
		}

		$fileCount = $fileCollection->count();

		$firstFile = $fileCollection->shift();
		if (is_null($firstFile))
		{
			return (new AddResult())->addError(new Error('Files sent with errors'));
		}

		$imagesCountLimit = Storage::instance()->getImagesCountLimitForBlankUpload();
		if ($firstFile->isImage && $fileCount > $imagesCountLimit)
		{
			return (new AddResult())->addError(
				new Error(
					Loc::getMessage('SIGN_SERVICE_BLANK_TOO_MANY_IMAGES', [
						'#COUNT#' => $imagesCountLimit,
					]),
					'FILE_TOO_MANY_IMAGES',
				),
			);
		}

		$preparedFileResult = $this->blankFileService->prepareFile($firstFile);
		if (!$preparedFileResult->isSuccess())
		{
			return (new AddResult())->addErrors($preparedFileResult->getErrors());
		}

		/** @var \Bitrix\Sign\File $resultFile */
		$resultFile = $preparedFileResult->getData()['file'];
		if ($firstFile->id !== null && $resultFile->getId() !== $firstFile->id)
		{
			$this->fileRepository->deleteById($firstFile->id);

			$firstFile = File::createByLegacyFile($resultFile);
		}

		$blank = $this->blankRepository->add(
			new Blank(
				title: $firstFile->name,
				fileCollection: new FileCollection($firstFile),
				scenario: $scenario,
				forTemplate: $forTemplate,
			),
		);
		$files = $fileCollection->toArray();

		if ($blank->isSuccess() && $firstFile->isImage)
		{
			$result = $this->blankFileService->addImagesToBlank($blank->getId(), $files);
			if (!$result->isSuccess())
			{
				return (new AddResult())->addErrors($result->getErrors());
			}
		}
		foreach ($fileCollection as $file)
		{
			if ($file->id !== null)
			{
				$this->fileRepository->deleteById($file->id);
			}
		}

		return $blank;
	}

	/**
	 * @param array<string|int> $files
	 *
	 * @return \Bitrix\Main\Entity\AddResult
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function createFromFileIds(array $files, string $scenario = BlankScenario::B2B, bool $forTemplate = false): AddResult
	{
		if (!in_array($scenario, BlankScenario::getAll(), true))
		{
			return (new AddResult())->addError(new Error('Wrong blank scenario'));
		}

		$fileController = new BlankUploadController([]);
		$uploader = new \Bitrix\UI\FileUploader\Uploader($fileController);

		$fileCollection = new FileCollection();

		foreach ($files as $fileId)
		{
			if (is_null($fileId))
			{
				return (new AddResult())->addError(new Error('Files sent with errors'));
			}

			$persistentFileId = null;
			if (is_string($fileId))
			{
				$pendingFiles = $uploader->getPendingFiles([$fileId]);
				$pendingFiles->makePersistent();
				$file = $pendingFiles->get($fileId);
				$persistentFileId = $file->getFileId();
			}

			$signFile = new \Bitrix\Sign\File((int)($persistentFileId ?: $fileId));
			$fileCollection->addItem(
				new File(
					$signFile->getName(),
					$signFile->getPath(),
					$signFile->getExtension(),
					$signFile->getId(),
					new FileContent($signFile->getContent()),
					$signFile->isImage()
				)
			);
		}

		return $this->createFromFile($fileCollection, $scenario, $forTemplate);
	}

	public function changeBlankTitleByDocument(Document $document, string $title): Result
	{
		if ($document->blankId === null)
		{
			return (new Result())->addError(new Error('Document has no connected blank'));
		}

		$blankItem = $this->blankRepository->getById($document->blankId);
		if ($blankItem === null)
		{
			return (new Result())->addError(new Error('No blank found for this document'));
		}

		$blankItem->title = $title;

		return $this->blankRepository->update($blankItem);
	}

    public function deleteWithResources(Blank $blankItem): Result
	{
        return $this->blankRepository->delete($blankItem->id);
    }

	public function rollbackById(int $blankId): Result
	{
		if (Container::instance()->getDocumentRepository()->getCountByBlankId($blankId) > 0)
		{
			return new Result();
		}

		$blank = $this->blankRepository->getById($blankId);
		if (!$blank)
		{
			return new Result();
		}

		return $this->deleteWithResources($blank);
	}
}
<?php

namespace Bitrix\Sign\Operation;

use Bitrix\Main;

use Bitrix\Sign\Item;
use Bitrix\Sign\Repository;
use Bitrix\Sign\Service;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Type\DocumentScenario;

class CloneBlankForDocument implements Contract\Operation
{
	public function __construct(
		private readonly Item\Document $document,
		private ?Repository\DocumentRepository $documentRepository = null,
		private ?Repository\BlankRepository $blankRepository = null,
		private ?Repository\BlockRepository $blockRepository = null,
		private ?Repository\Blank\ResourceRepository $resourceRepository = null,
		private ?Repository\FileRepository $fileRepository = null,
	)
	{
		$this->documentRepository ??= Service\Container::instance()->getDocumentRepository();
		$this->blankRepository ??= Service\Container::instance()->getBlankRepository();
		$this->blockRepository ??= Service\Container::instance()->getBlockRepository();
		$this->resourceRepository ??= Service\Container::instance()->getBlankResourceRepository();
		$this->fileRepository ??= Service\Container::instance()->getFileRepository();
	}

	public function getDocument(): Item\Document
	{
		return $this->document;
	}

	public function launch(): Main\Result
	{
		$result = new Main\Result();

		if (!$this->document->id)
		{
			return $result->addError(new Main\Error("Document not found"));
		}

		if (!$this->document->blankId)
		{
			return $result->addError(new Main\Error("Document item field `blankId` is empty"));
		}

		$blank = $this->blankRepository->getById($this->document->blankId);
		if (!$blank)
		{
			return $result->addError(new Main\Error("Blank not found"));
		}

		if (DocumentScenario::isB2EScenario($this->document->scenario))
		{
			$blank->title = $this->document->title;
		}

		$oldBlankId = $blank->id;
		$blank->forTemplate = $blank->forTemplate || $this->document->isTemplated();
		//TODO: add all resources later
		$result = $this->blankRepository->clone($blank);
		if (!$result->isSuccess())
		{
			return $result;
		}
		$resource = $this->resourceRepository->getFirstByBlankId($blank->id);
		$blank->id = $result->getId();
		if ($resource !== null)
		{
			$file = $this->fileRepository->copyById($resource->fileId);
			if ($file && $file->id !== null && $blank->id !== null)
			{
				$resource = new Item\Blank\Resource(
					null,
					$blank->id,
					$file->id
				);
				$resourceAddResult = $this->resourceRepository->add($resource);
				if (!$resourceAddResult->isSuccess())
				{
					return $result->addErrors($resourceAddResult->getErrors());
				}
			}
		}

		$result = $this->copyBlocks($blank, $oldBlankId);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$this->document->blankId = $blank->id;

		return $this->documentRepository->update($this->document);
	}

	private function copyBlocks(Item\Blank $newBlank, int $oldBlankId): Main\Result
	{
		$blockCollection = $this->blockRepository->getCollectionByBlankId($oldBlankId);
		if ($blockCollection->isEmpty())
		{
			return new Main\Result();
		}

		foreach ($blockCollection as $block)
		{
			$block->id = null;
			$block->blankId = $newBlank->id;
		}

		return $this->blockRepository->addCollection($blockCollection);
	}
}

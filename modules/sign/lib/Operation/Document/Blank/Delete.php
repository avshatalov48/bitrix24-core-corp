<?php

namespace Bitrix\Sign\Operation\Document\Blank;

use Bitrix\Main;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Item\Blank;
use Bitrix\Sign\Item\BlockCollection;
use Bitrix\Sign\Repository\Blank\ResourceRepository;
use Bitrix\Sign\Repository\BlankRepository;
use Bitrix\Sign\Repository\BlockRepository;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Repository\FileRepository;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Service\Container;

class Delete implements Operation
{
	public const ERROR_BLANK_USED_FOR_RESENT_DOCUMENTS = 'BLANK_USED_FOR_RESENT_DOCUMENTS';
	public const ERROR_BLANK_USED_IN_DOCUMENTS = 'BLANK_USED_IN_DOCUMENTS';

	private readonly DocumentRepository $documentRepository;
	private readonly BlockRepository $blockRepository;
	private readonly BlankRepository $blankRepository;
	private readonly FileRepository $fileRepository;
	private readonly ResourceRepository $blankResourceRepository;

	public function __construct(private readonly Blank $blank)
	{
		$container = Container::instance();

		$this->documentRepository = $container->getDocumentRepository();
		$this->blockRepository = $container->getBlockRepository();
		$this->blankRepository = $container->getBlankRepository();
		$this->fileRepository = $container->getFileRepository();
		$this->blankResourceRepository = $container->getBlankResourceRepository();
	}

	public function launch(): Main\Result
	{
		if ($this->blank->id === null)
		{
			return Result::createByErrorData(message: 'Blank not found');
		}

		if (!$this->blank->forTemplate)
		{
			return Result::createByErrorData(
				"Blank used for resent documents",
				self::ERROR_BLANK_USED_FOR_RESENT_DOCUMENTS,
			);
		}

		if ($this->documentRepository->getCountByBlankId($this->blank->id) > 0)
		{
			return Result::createByErrorData(
				"Blank is used in documents",
				self::ERROR_BLANK_USED_IN_DOCUMENTS,
			);
		}

		if ($this->blank->blockCollection === null)
		{
			$this->blockRepository->loadBlocks($this->blank);
		}
		$blocks = $this->blank->blockCollection ?? new BlockCollection();

		$fileIds = [];
		foreach ($blocks as $block)
		{
			$fileId = $block->data['fileId'] ?? null;
			if (is_numeric($fileId))
			{
				$fileIds[] = (int)$fileId;
			}
		}
		if (!empty($fileIds))
		{
			$result = $this->fileRepository->deleteByIds($fileIds);
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		$blockIds = $blocks->getIds();
		if (!empty($blockIds))
		{
			$result = $this->blockRepository->deleteByIds($blockIds);
			if (!$result->isSuccess())
			{
				return $result;
			}
		}
		$result = $this->deleteResources();
		if (!$result->isSuccess())
		{
			return $result;
		}

		return $this->blankRepository->delete($this->blank->id);
	}

	private function deleteResources(): Main\Result
	{
		$fileIds = $this->blankResourceRepository->listFileIdsByBlankId($this->blank->id);
		if (!empty($fileIds))
		{
			$result = $this->fileRepository->deleteByIds($fileIds);
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		return $this->blankResourceRepository->deleteByBlankId($this->blank->id);
	}
}

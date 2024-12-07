<?php

namespace Bitrix\Sign\Blanks\Block\Configuration;

use Bitrix\Sign\Blanks\Block\Configuration;
use Bitrix\Sign\Item;
use Bitrix\Sign\Repository\FileRepository;
use Bitrix\Sign\Service\Container;

class Sign extends Configuration
{
	private \Bitrix\Sign\Repository\FileRepository $fileRepository;

	public function __construct(
		?FileRepository $fileRepository = null
	)
	{
		$this->fileRepository = $fileRepository ?? Container::instance()->getFileRepository();
	}

	function loadData(Item\Block $block, Item\Document $document, ?Item\Member $member = null): array
	{
		$data = $block->data;

		if ($member === null)
		{
			return $data;
		}

		$signFileId = $member->signatureFileId;
		if ($signFileId === null)
		{
			return $data;
		}

		$data['fileId'] = $signFileId;

		return $data;
	}

	public function getViewSpecificData(Item\Block $block): ?array
	{
		$fileId = $block->data['fileId'] ?? null;
		if ($fileId === null)
		{
			return null;
		}

		$file = $this->fileRepository->getById($fileId, true);
		if ($file?->content?->data === null)
		{
			return null;
		}

		return [
			'base64' => base64_encode($file->content->data),
		];
	}
}
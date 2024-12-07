<?php

namespace Bitrix\Sign\Blanks\Block\Configuration;

use Bitrix\Sign\Blanks\Block\Configuration;
use Bitrix\Sign\Item;
use Bitrix\Sign\Repository\FileRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\MemberService;

class MyStamp extends Configuration
{
	private \Bitrix\Sign\Repository\FileRepository $fileRepository;
	private MemberService $memberService;

	public function __construct(
		?FileRepository $fileRepository = null,
		?MemberService $memberService = null,
	)
	{
		$this->fileRepository = $fileRepository ?? Container::instance()->getFileRepository();
		$this->memberService = $memberService ?? Container::instance()->getMemberService();
	}

	function loadData(Item\Block $block, Item\Document $document, ?Item\Member $member = null): array
	{
		$data = $block->data;

		if ($member === null)
		{
			return $data;
		}

		$fileId = $member->stampFileId;
		if ($fileId !== null)
		{
			$file = $this->fileRepository->getById($fileId)
				?? $this->memberService->getStampFileFromMemberOrEntity($member)
			;
		}
		else
		{
			$file = $this->memberService->getStampFileFromMemberOrEntity($member);
		}

		$fileId = $file?->id;
		if ($fileId !== null)
		{
			$data['fileId'] = $fileId;
		}

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
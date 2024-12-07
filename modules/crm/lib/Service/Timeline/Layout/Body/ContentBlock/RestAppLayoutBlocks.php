<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

class RestAppLayoutBlocks extends ContentBlock
{
	public function __construct(
		private readonly int $itemTypeId,
		private readonly int $itemId,
		private readonly array $restAppInfo,
		/** @var ContentBlock[] $contentBlocks */
		private readonly array $contentBlocks,
	)
	{
	}

	public function getRendererName(): string
	{
		return 'RestAppLayoutBlocks';
	}

	public function getContentBlockName(): string
	{
		$clientId = $this->restAppInfo['CLIENT_ID'] ?? '';

		return "rest_app_content_blocks_{$clientId}";
	}

	public function getProperties(): ?array
	{
		return [
			'itemId' => $this->itemId,
			'itemTypeId' => $this->itemTypeId,
			'restAppInfo' => $this->prepareRestAppInfo(),
			'contentBlocks' => $this->contentBlocks,
		];
	}

	private function prepareRestAppInfo(): array
	{
		return [
			'title' => $this->restAppInfo['MENU_NAME'],
			'clientId' => $this->restAppInfo['CLIENT_ID'],
		];
	}
}

<?php

namespace Bitrix\Sign\Service\Result\Sign\Block;

use Bitrix\Sign\Item;

class LoadBlocksAndDataByDocumentResult extends \Bitrix\Main\Result
{
	protected $data = [
		'blocks' => null
	];

	public function setBlocks(Item\BlockCollection $blocks): static
	{
		$this->data['blocks'] = $blocks;
		return $this;
	}

	public function getBlocks(): ?Item\BlockCollection
	{
		return $this->data['blocks'];
	}
}
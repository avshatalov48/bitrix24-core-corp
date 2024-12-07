<?php

namespace Bitrix\Sign\Operation\Block;

use Bitrix\Main;
use Bitrix\Sign\Blanks\Block;
use Bitrix\Sign\Exception\SignException;
use Bitrix\Sign\Item;
use Bitrix\Sign\Repository;
use Bitrix\Sign\Service;
use Bitrix\Sign\Contract;

class RefillForBlank implements Contract\Operation
{
	public function __construct(
		private int $blankId,
		private Item\BlockCollection $blockCollection,
		private ?Repository\BlockRepository $blockRepository = null,
		private ?Block\Factory $blockFactory = null,
	)
	{
		$this->blockRepository ??= Service\Container::instance()->getBlockRepository();
		$this->blockFactory ??= new Block\Factory();
	}

	public function launch(): Main\Result
	{
		$result = new Main\Result();

		$blocksBeforeSave = $this->blockRepository->getCollectionByBlankId($this->blankId);
		foreach ($this->blockCollection as $block)
		{
			try
			{
				$configuration = $this->blockFactory->getConfigurationByCode($block?->code ?? '');
			}
			catch (SignException $exception)
			{
				return $result->addError(new Main\Error($exception->getMessage()));
			}

			$result = $configuration->validate($block);
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		foreach ($this->blockCollection as $block)
		{
			$this->cleanBlockData($block);

			$block->id = null;
			$result = $this->blockRepository->add($block);
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		$oldBlockIds = $blocksBeforeSave->getIds();

		return $this->blockRepository->deleteByIds($oldBlockIds);
	}

	private function cleanBlockData(Item\Block $block): void
	{
		if (array_key_exists(Block\Configuration::VIEW_SPECIFIC_DATA_KEY, $block->data))
		{
			unset($block->data[Block\Configuration::VIEW_SPECIFIC_DATA_KEY]);
		}
	}
}
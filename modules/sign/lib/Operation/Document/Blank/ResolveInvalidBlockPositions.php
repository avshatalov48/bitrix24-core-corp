<?php

namespace Bitrix\Sign\Operation\Document\Blank;

use Bitrix\Main;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Item\Blank;
use Bitrix\Sign\Item\BlockCollection;
use Bitrix\Sign\Repository\Blank\ResourceRepository;
use Bitrix\Sign\Repository\BlockRepository;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Service\Container;

class ResolveInvalidBlockPositions implements Operation
{
	private readonly BlockRepository $blockRepository;

	public function __construct(
		private readonly Blank $blank,
		private readonly int $pagesAmount,
	)
	{
		$container = Container::instance();

		$this->blockRepository = $container->getBlockRepository();
	}

	public function launch(): Main\Result
	{
		if ($this->blank->id === null)
		{
			return Result::createByErrorMessage('Blank id is not set');
		}

		$blocks = $this->blank->blockCollection;
		if ($blocks === null)
		{
			$blocks = $this->blockRepository->loadBlocks($this->blank);
		}

		if ($blocks === null)
		{
			return Result::createByErrorMessage('No blocks found');
		}

		if ($blocks->isEmpty())
		{
			return new Main\Result();
		}

		$blocksWithCorrectedPage = $blocks->copyAndSetPageIfItGreaterThan($this->pagesAmount);
		if ($blocksWithCorrectedPage->isEmpty())
		{
			return new Main\Result();
		}

		return $this->blockRepository->updateBlocks($blocksWithCorrectedPage);
	}
}
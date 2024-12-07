<?php

namespace Bitrix\Sign\Service\Sign;

use Bitrix\Main;
use Bitrix\Sign\Blanks;
use Bitrix\Sign\Blanks\Block\Configuration;
use Bitrix\Sign\Exception\SignException;
use Bitrix\Sign\Item;
use Bitrix\Sign\Repository;
use Bitrix\Sign\Service;
use Bitrix\Sign\Service\Result\Sign\Block\LoadBlocksAndDataByDocumentResult;
use Bitrix\Sign\Type;

class BlockService
{
	private Blanks\Block\Factory $blockFactory;
	private Repository\MemberRepository $memberRepository;
	private DocumentService $documentService;

	public function __construct(
		private ?Repository\BlankRepository $blankRepository = null,
		private ?Repository\BlockRepository $blockRepository = null,
		?Blanks\Block\Factory $blockFactory = null,
	)
	{
		$this->blankRepository ??= Service\Container::instance()->getBlankRepository();
		$this->blockRepository ??= Service\Container::instance()->getBlockRepository();
		$this->documentService ??= Service\Container::instance()->getDocumentService();
		$this->memberRepository ??= Service\Container::instance()->getMemberRepository();
		$this->blockFactory = $blockFactory ?? new Blanks\Block\Factory(blockService: $this);
	}

	public function deleteBlock(Item\Document $document, Item\Block $block): Main\Result
	{
		$result = new Main\Result();

		if ($document->id === null)
		{
			return $result->addError(new Main\Error('Document field `id` is empty'));
		}
		if ($this->documentService->canEditBlank($document))
		{
			return $result->addError(new Main\Error("Blank for this document can't be edited"));
		}
		if ($document->blankId === null)
		{
			return $result->addError(new Main\Error('Document field `blankId` is empty'));
		}

		if ($block->id === null)
		{
			return $result->addError(new Main\Error('Blank field `id` is empty'));
		}

		$blank = $this->blankRepository->getById($document->blankId);
		if ($blank === null)
		{
			return $result->addError(new Main\Error('Blank for document not found'));
		}

		if ($block->blankId !== $blank->id)
		{
			return $result->addError(new Main\Error('Block is not connected to this blank'));
		}

		return $this->blockRepository->deleteById($block->id);
	}

	public function loadBlocksAndDataByDocument(
		Item\Document $document,
		bool $skipSecurity = false,
	): LoadBlocksAndDataByDocumentResult
	{
		$result = new LoadBlocksAndDataByDocumentResult();
		if ($document->blankId === null)
		{
			$result->addError(new Main\Error('Document blank not found'));
			return $result;
		}
		if ($document->id === null)
		{
			$result->addError(new Main\Error('Document doesnt exist'));
			return $result;
		}

		$blank = $this->blankRepository->getById($document->blankId);
		if ($blank === null)
		{
			$result->addError(new Main\Error('Document blank not found'));
			return $result;
		}
		$blocks = $this->blockRepository->loadBlocks($blank) ?? new Item\BlockCollection();

		if (
			Type\DocumentScenario::isB2EScenario($document->scenario)
			&& $document->scheme === Type\Document\SchemeType::ORDER
		)
		{
			$blocks = $blocks->filterExcludeRole(Type\Member\Role::SIGNER);
		}

		$memberByRole = [];
		foreach ($blocks as $block)
		{
			if ($block->role && !array_key_exists($block->role, $memberByRole))
			{
				$memberByRole[$block->role] = $this->memberRepository
					->listByDocumentIdWithRole($document->id, $block->role, 1)
					->getFirst()
				;
			}
			$member = $memberByRole[$block->role] ?? null;
			$loadDataResult = $this->loadData($block, $document, $member, $skipSecurity);
			if (!$loadDataResult->isSuccess())
			{
				return $result->addErrors($loadDataResult->getErrors());
			}

			$block->data = $loadDataResult->getData();
		}

		return $result->setData([
			'blocks' => $blocks,
		]);
	}

	public function loadData(
		Item\Block $block,
		Item\Document $document,
		?Item\Member $member = null,
		bool $skipSecurity = false,
	): Main\Result
	{
		try
		{
			$configuration = $this->blockFactory->getConfigurationByCode($block->code, $skipSecurity);
		}
		catch (SignException $e)
		{
			return (new Main\Result())->addError(new Main\Error("Cant create block with code: `{$block->code}`"));
		}
		$resultData = $configuration->loadData($block, $document, $member);
		$viewData = $configuration->getViewSpecificData($block);
		if ($viewData !== null)
		{
			$resultData[Configuration::VIEW_SPECIFIC_DATA_KEY] = $viewData;
		}

		return (new Main\Result())->setData($resultData);
	}
}

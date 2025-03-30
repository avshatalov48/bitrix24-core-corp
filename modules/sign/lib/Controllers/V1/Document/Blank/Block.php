<?php

namespace Bitrix\Sign\Controllers\V1\Document\Blank;

use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Blanks;
use Bitrix\Sign\Blanks\Block\Factory;
use Bitrix\Sign\Serializer\ItemPropertyJsonSerializer;
use Bitrix\Sign\Service;
use Bitrix\Sign\Repository;
use Bitrix\Sign\Operation;
use Bitrix\Sign\Item;
use Bitrix\Sign\Attribute;
use Bitrix\Main;
use Bitrix\Sign\Type;
use Bitrix\Sign\Type\Access\AccessibleItemType;
use Bitrix\Sign\Compatibility\FrontendBlockParty;

class Block extends \Bitrix\Sign\Engine\Controller
{
	private Service\Sign\BlockService $blockService;

	private Repository\DocumentRepository $documentRepository;
	private Repository\BlankRepository $blankRepository;
	private Repository\MemberRepository $memberRepository;
	private ItemPropertyJsonSerializer $itemPropertyJsonSerializer;

	public function __construct(Main\Request $request = null)
	{
		$this->blockService = Service\Container::instance()->getSignBlockService();
		$this->blankRepository = Service\Container::instance()->getBlankRepository();
		$this->documentRepository = Service\Container::instance()->getDocumentRepository();
		$this->memberRepository = Service\Container::instance()->getMemberRepository();
		$this->itemPropertyJsonSerializer = new ItemPropertyJsonSerializer();

		parent::__construct($request);
	}

	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'documentUid'
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'documentUid'
		),
	)]
	public function saveAction(string $documentUid, array $blocks): array
	{
		$document = $this->documentRepository->getByUid($documentUid);
		if (!$document)
		{
			$this->addError(new Main\Error('Document not found'));
			return [];
		}

		if (!$document->blankId)
		{
			$this->addError(new Main\Error('Blank is not assigned'));
			return [];
		}

		if ($this->documentRepository->getCountByBlankId($document->blankId) > 1)
		{
			$cloneResult = (new Operation\CloneBlankForDocument($document))->launch();
			if (!$cloneResult->isSuccess())
			{
				$this->addErrors($cloneResult->getErrors());
				return [];
			}
		}

		$blockCollection = new Item\BlockCollection();

		$requiredBlockDataKeys = ['code', 'party', 'position', 'style', 'data'];
		foreach ($blocks as $blockData)
		{
			foreach ($requiredBlockDataKeys as $key)
			{
				if (!array_key_exists($key, $blockData))
				{
					$this->addError(new Main\Error("Block data must contains key: `{$key}`"));
					return [];
				}
			}
			[
				'code' => $code,
				'party' => $party,
				'position' => $position,
				'style' => $style,
				'data' => $data,
			] = $blockData;

			try
			{
				/** @var Item\Block\Position $position */
				$position = (new ItemPropertyJsonSerializer())->deserialize((array)$position, Item\Block\Position::class);
			}
			catch (\Exception $exception)
			{
				$this->addError(new Main\Error("Block position has invalid data"));
				return [];
			}
			try
			{
				/** @var Item\Block\Style $style */
				$style = (new ItemPropertyJsonSerializer())->deserialize($style, Item\Block\Style::class);
			}
			catch (\Exception $exception)
			{
				$this->addError(new Main\Error("Block style has invalid data"));
				return [];
			}
			$frontendBlockService = $this->container->getFrontendBlockService();

			$blockCollection->add(new Item\Block(
				party: $frontendBlockService->calculateMemberParty((int)$party, $document),
				type: (new Factory())->getTypeByCode($code),
				code: $code,
				blankId: $document->blankId,
				position: $position,
				data: $data,
				id: null,
				style: $style,
				role: $frontendBlockService->getRole($party),
			));
		}

		$operation = new Operation\Block\RefillForBlank(
			$document->blankId,
			blockCollection: $blockCollection,
		);

		$result = $operation->launch();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return [];
		}

		return [];
	}

	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'documentUid'
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'documentUid'
		)
	)]
	public function loadDataAction(string $documentUid, array $blocks): array
	{
		$document = $this->documentRepository->getByUid($documentUid);
		if (!$document)
		{
			$this->addError(new Main\Error('Document not found'));
			return [];
		}
		$blank = $this->blankRepository->getById($document->blankId ?? 0);
		if (!$blank)
		{
			$this->addError(new Main\Error('Blank not found'));
			return [];
		}

		$blockFactory = new Factory();

		$result = [];
		$frontendBlockService = $this->container->getFrontendBlockService();
		foreach ($blocks as $block)
		{
			$frontendParty = $block['part'] ?? 0;
			$item = $blockFactory->makeItem(
				document: $document,
				code: $block['code'] ?? '',
				party: $frontendBlockService->calculateMemberParty((int)$frontendParty, $document),
				data: $block['data'] ?? null,
			);

			$result[] = [
				'data' => $item->data,
			];
		}

		return $result;
	}

	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'documentUid'
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'documentUid'
		)
	)]
	public function loadByDocumentAction(string $documentUid): array
	{
		$document = $this->documentRepository->getByUid($documentUid);
		if ($document === null)
		{
			$this->addError(new Main\Error('Document not found'));
			return [];
		}

		$loadResult = $this->blockService->loadBlocksAndDataByDocument($document);
		if (!$loadResult->isSuccess())
		{
			$this->addErrors($loadResult->getErrors());
			return [];
		}

		$blocks = $loadResult->getData()['blocks'] ?? new Item\BlockCollection();
		$blocks = $blocks->filter(
			fn(Item\Block $block) => $this->filterBlock($document, $block)
		);

		$result = [];

		$lastPartyMembersCount = $this->memberRepository->countByDocumentIdAndParty($document->id, $document->parties);
		foreach ($blocks as $block)
		{
			if (
				Type\DocumentScenario::isB2EScenario($document->scenario)
				&& $block->role === Type\Member\Role::SIGNER
				&& $lastPartyMembersCount > 1
			)
			{
				$block->data['text'] = '';
			}

			$frontendBlockService = $this->container->getFrontendBlockService();
			$result[] = [
				'id' => $block->id,
				'code' => $block->code,
				'data' => $block->data,
				'type' => $block->type,
				'party' => $frontendBlockService->getByRole($block->role),
				'style' => $block->style !== null ? $this->itemPropertyJsonSerializer->serialize($block->style) : null,
				'position' => $block->position !== null ? $this->itemPropertyJsonSerializer->serialize($block->position) : null,
			];
		}

		return $result;
	}

	private function filterBlock(
		Item\Document $document,
		Item\Block $block
	): bool
	{
		if ($block->code === Type\BlockCode::B2E_HCMLINK_REFERENCE)
		{
			if (!$document->hcmLinkCompanyId)
			{
				return false;
			}

			$field = $block->data['field'] ?? null;
			if (empty($field))
			{
				return false;
			}

			$hcmLinkCompanyId = Service\Container::instance()
				->getHcmLinkFieldService()
				->extractCompanyIdFromFieldName($block->data['field'])
			;

			if (!$hcmLinkCompanyId)
			{
				return false;
			}

			return $document->hcmLinkCompanyId === $hcmLinkCompanyId;
		}

		return true;
	}
}

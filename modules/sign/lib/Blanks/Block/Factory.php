<?php

namespace Bitrix\Sign\Blanks\Block;

use Bitrix\Sign\Compatibility\Role;
use Bitrix\Sign\Exception\SignException;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Integration\HumanResources\HcmLinkFieldService;
use Bitrix\Sign\Type;
use Bitrix\Sign\Item;
use Bitrix\Sign\Service;
use Bitrix\Sign\Service\Providers\LegalInfoProvider;

class Factory
{
	private MemberRepository $memberRepository;
	private Service\Sign\BlockService $blockService;
	private readonly HcmLinkFieldService $hcmLinkFieldService;
	private readonly LegalInfoProvider $legalInfoProvider;

	public function __construct(
		?MemberRepository $memberRepository = null,
		?Service\Sign\BlockService $blockService = null,
		?HcmLinkFieldService $hcmLinkFieldService = null,
		?LegalInfoProvider $legalInfoProvider = null,
	)
	{
		$this->memberRepository = $memberRepository ?? Container::instance()->getMemberRepository();
		$this->blockService = $blockService ?? Container::instance()->getSignBlockService();
		$this->hcmLinkFieldService = $hcmLinkFieldService ?? Container::instance()->getHcmLinkFieldService();
		$this->legalInfoProvider = $legalInfoProvider ?? Container::instance()->getLegalInfoProvider();
	}

	/**
	 * @throws SignException
	 */
	public function getConfigurationByCode(string $code, bool $skipSecurity = false): Configuration
	{
		if (!in_array($code, Type\BlockCode::getAll(), true))
		{
			throw new SignException("No block configuration for code $code");
		}

		return match ($code)
		{
			Type\BlockCode::TEXT => new Configuration\Text(),
			Type\BlockCode::NUMBER => new Configuration\Number(),
			Type\BlockCode::DATE => new Configuration\Date(),

			Type\BlockCode::MY_SIGN => new Configuration\MySign(),
			Type\BlockCode::MY_STAMP => new Configuration\MyStamp(),
			Type\BlockCode::MY_REFERENCE => new Configuration\MyReference(),
			Type\BlockCode::MY_REQUISITES => new Configuration\MyRequisites(),

			Type\BlockCode::SIGN => new Configuration\Sign(),
			Type\BlockCode::STAMP => new Configuration\Stamp(),
			Type\BlockCode::REFERENCE => new Configuration\Reference(),
			Type\BlockCode::REQUISITES => new Configuration\Requisites(),

			Type\BlockCode::B2E_MY_REFERENCE => new Configuration\B2e\MyB2eReference($skipSecurity),
			Type\BlockCode::B2E_REFERENCE => new Configuration\B2e\B2eReference($skipSecurity),
			Type\BlockCode::EMPLOYEE_DYNAMIC => new Configuration\B2e\EmployeeDynamic(),
			Type\BlockCode::B2E_HCMLINK_REFERENCE => new Configuration\B2e\HcmLinkReference(),
		};
	}

	/**
	 * @param Item\Document $document
	 * @param string $code
	 * @param int $party
	 * @param array|null $data
	 * @param bool $skipSecurity
	 * @param Type\Member\Role::*|null $role
	 *
	 * @return Item\Block
	 */
	public function makeItem(
		Item\Document $document,
		string $code,
		int $party,
		?array $data = null,
		bool $skipSecurity = false,
		?string $role = null,
	): Item\Block
	{
		$configuration = $this->getConfigurationByCode($code, $skipSecurity);

		$item =  new Item\Block(
			party: $party,
			type: $this->getTypeByCode($code),
			code: $code,
			data: $data ?? [],
			role: $role ?? Role::createForBlock($party, $document->parties),
		);
		// we need only first member, and other to check that count is more that 1
		$membersByParty = $this->memberRepository->listByDocumentIdWithParty($document->id, $party, 2);

		$result = $this->blockService->loadData($item, $document, $membersByParty->getFirst(), $skipSecurity);
		if (!$result->isSuccess())
		{
			return $item;
		}
		$item->data = $result->getData();

		if (
			Type\DocumentScenario::isB2EScenario($document->scenario)
			&& $party === $document->parties
			&& $membersByParty->count() > 1
		)
		{
			$item->data['text'] = '';
		}

		$viewData = $configuration->getViewSpecificData($item);
		if ($viewData !== null)
		{
			$item->data[Configuration::VIEW_SPECIFIC_DATA_KEY] = $viewData;
		}

		return $item;
	}

	public function getTypeByCode(string $code): string
	{
		return match ($code)
		{
			Type\BlockCode::SIGN,
			Type\BlockCode::STAMP,
			Type\BlockCode::MY_STAMP,
			Type\BlockCode::MY_SIGN => Type\BlockType::IMAGE,

			Type\BlockCode::MY_REQUISITES, Type\BlockCode::REQUISITES => Type\BlockType::MULTILINE_TEXT,
			default => Type\BlockType::TEXT
		};
	}

	public function makeStubBlockByRequiredField(
		Item\Document $document,
		Item\B2e\RequiredField $requiredField,
		int $party,
	): ?Item\Block
	{
		$name = null;
		$code = null;
		if ($document->hcmLinkCompanyId && $this->hcmLinkFieldService->isAvailable())
		{
			$name = $this->hcmLinkFieldService->getHcmRequiredFieldSelectorNameByType(
				integrationId: $document->hcmLinkCompanyId,
				fieldType: $requiredField->type,
				party: $party,
			);
			$code = Type\BlockCode::B2E_HCMLINK_REFERENCE;
		}

		if (!$name)
		{
			$name = $this->legalInfoProvider->getFirstFieldNameByType($requiredField->type);
			$code = Type\BlockCode::getB2eReferenceCodeByRole($requiredField->role);
		}

		if (!$name)
		{
			return null;
		}

		return $this->makeItem(
			document: $document,
			code: $code,
			party: $party,
			data: ['field' => $name],
			skipSecurity: true,
			role: $requiredField->role,
		);
	}
}

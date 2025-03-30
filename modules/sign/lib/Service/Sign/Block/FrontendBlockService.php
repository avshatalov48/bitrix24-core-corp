<?php

namespace Bitrix\Sign\Service\Sign\Block;

use Bitrix\Sign\Item;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\BlockParty;
use Bitrix\Sign\Type;
use Bitrix\Sign\Type\Document\InitiatedByType;

/**
 * it need for backward compatibility
 */
final class FrontendBlockService
{
	private readonly MemberRepository $memberRepository;

	public function __construct(?MemberRepository $memberRepository = null)
	{
		$this->memberRepository = $memberRepository ?? Container::instance()->getMemberRepository();
	}

	public function calculateMemberParty(int $blockParty, Item\Document $document): int
	{
		if ($document->initiatedByType === InitiatedByType::EMPLOYEE)
		{
			return match ($blockParty)
			{
				BlockParty::COMMON_PARTY => 0,
				BlockParty::LAST_PARTY => 1,
				default => $document->parties,
			};
		}

		return match ($document->isTemplated())
		{
			true => match ($blockParty)
			{
				BlockParty::COMMON_PARTY => 0,
				BlockParty::NOT_LAST_PARTY => $document->parties,
				default => $document->parties + 1,
			},
			default => match ($blockParty)
			{
				BlockParty::COMMON_PARTY => 0,
				BlockParty::LAST_PARTY => $document->parties,
				default => $document->parties - 1,
			},
		};
	}

	public function getRole(int $frontParty): ?string
	{
		return match($frontParty)
		{
			BlockParty::NOT_LAST_PARTY => Type\Member\Role::ASSIGNEE,
			BlockParty::LAST_PARTY => Type\Member\Role::SIGNER,
			default => null,
		};
	}

	public function getByRole(?string $role): int
	{
		return match ($role)
		{
			Type\Member\Role::ASSIGNEE => BlockParty::NOT_LAST_PARTY,
			Type\Member\Role::SIGNER => BlockParty::LAST_PARTY,
			default => BlockParty::COMMON_PARTY,
		};
	}
}

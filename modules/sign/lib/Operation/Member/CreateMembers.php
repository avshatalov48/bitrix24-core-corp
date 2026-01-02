<?php

namespace Bitrix\Sign\Operation\Member;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Integration\Bitrix24\B2eTariff;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\MemberCollection;
use Bitrix\Sign\Operation\Document\Validation\ValidateRequiredFields;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Integration\HumanResources\HcmLinkService;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Type\Member\ChannelType;
use Bitrix\Sign\Type\Member\ChannelValue;
use Bitrix\Sign\Type\Member\EntityType;
use Bitrix\Sign\Type\Member\Role;

final class CreateMembers implements Contract\Operation
{
	public function __construct(
		private readonly Document $document,
		private readonly int $party,
		private readonly array $userIds,
		private ?HcmLinkService $hcmLinkService = null,
		private ?MemberService $memberService = null,
		private ?MemberRepository $memberRepository = null,
	)
	{
		$this->hcmLinkService ??= Container::instance()->getHcmLinkService();
		$this->memberService ??= Container::instance()->getMemberService();
		$this->memberRepository ??= Container::instance()->getMemberRepository();
	}

	public function launch(): Result
	{
		$memberCollection = new MemberCollection();
		foreach ($this->userIds as $userId)
		{
			$memberCollection->add(
				new \Bitrix\Sign\Item\Member(
					documentId: $this->document->id,
					party: $this->party,
					channelType: ChannelType::IDLE,
					channelValue: ChannelValue::IDLE_VALUE,
					entityType: EntityType::USER,
					entityId: $userId,
					role: Role::SIGNER,
				),
			);
		}

		$tariffRestrictionCheckResult = $this->checkTariffLimitations($this->document, $memberCollection);
		if (!$tariffRestrictionCheckResult->isSuccess())
		{
			$this->memberService->cleanByDocumentId($this->document->id);
			return $tariffRestrictionCheckResult;
		}

		if ($this->document->representativeId === null)
		{
			return (new Result())->addError(new Error('Representative ID is not set'));
		}

		$this->hcmLinkService->fillOneLinkedMembersWithEmployeeId(
			$this->document,
			$memberCollection,
			$this->document->representativeId,
		);

		return $this->memberRepository->addMany($memberCollection);
	}

	/**
	 * @throws SystemException
	 */
	private function checkTariffLimitations(Document $document, MemberCollection $memberCollection): Result
	{
		if ($document->id === null)
		{
			throw new SystemException('Document ID must be set');
		}

		$result = new Result();

		$currentSignersCount = $this->memberRepository->countMembersByDocumentIdAndRoleAndStatus($document->id, [], Role::SIGNER);
		$uniqSignersCount = $currentSignersCount + $memberCollection->count();
		if (B2eTariff::instance()->isB2eSignersCountRestricted($uniqSignersCount))
		{
			$result->addError(B2eTariff::instance()->getSignersCountAccessError());
		}

		return $result;
	}
}
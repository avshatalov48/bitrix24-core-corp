<?php

namespace Bitrix\Sign\Service\Sign\Member;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main;
use Bitrix\Sign\Access\AccessController;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Item;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Restriction;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\DocumentScenario;
use Bitrix\Sign\Type\Member\ChannelType;

final class CommunicationService
{
	private MemberRepository $memberRepository;
	private ?AccessController $accessController;
	private DocumentRepository $documentRepository;

	public function __construct(
		/** @var ?int $userId it runs from agent if $userId === null */
		private ?int $userId,
		?MemberRepository $memberRepository = null,
		?AccessController $accessController = null,
		?DocumentRepository $documentRepository = null,
	)
	{
		$this->memberRepository = $memberRepository ?? Container::instance()->getMemberRepository();
		$this->accessController = $accessController;
		$this->accessController ??= $userId !== null
			? new AccessController($this->userId)
			: null
		;
		$this->documentRepository = $documentRepository ?? Container::instance()->getDocumentRepository();
	}

	public function modifyMembersChannelType(Item\MemberCollection $members, string $channelType): Main\Result
	{
		$result = $this->checkModifyMembersChannelTypeData($members, $channelType);
		if (!$result->isSuccess())
		{
			return $result;
		}

		foreach ($members as $member)
		{
			$member->channelType = $channelType;
			$result = $this->validateMemberChannelLicenceRestrictions($member);
			if (!$result->isSuccess())
			{
				return $result;
			}
		}
		foreach ($members as $member)
		{
			$result = $this->memberRepository->update($member);
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		return new Main\Result();
	}

	public function validateMemberChannelLicenceRestrictions(Item\Member $member): Main\Result
	{
		if ($member->channelType === ChannelType::PHONE && !Restriction::isSmsAllowed())
		{
			return (new Main\Result())->addError(
				new Main\Error(Loc::getMessage('SIGN_SERVICE_MEMBER_CHANNEL_NOT_ALLOWED_BY_TARIFF'), 'TARIFF_SMS_RESTRICTION')
			);
		}

		return new Main\Result();
	}

	private function checkModifyMembersChannelTypeData(Item\MemberCollection $members, string $channelType): Main\Result
	{
		$result = new Main\Result();

		if (!ChannelType::isValid($channelType))
		{
			return $result->addError(new Main\Error("Invalid channel type"));
		}

		$firstMember = $members->getFirst();
		if ($firstMember === null)
		{
			return $result->addError(new Main\Error("Members has different documents"));
		}

		$memberHasEqualDocument = $members->all(
			static fn(Item\Member $member) => $member->documentId === $firstMember->documentId
		);
		if (!$memberHasEqualDocument)
		{
			return $result->addError(new Main\Error("Members has different documents"));
		}

		$document = $this->documentRepository->getById($firstMember->documentId);
		if ($document === null)
		{
			return $result->addError(new Main\Error("Members document doesnt exist"));
		}



		$accessAction = DocumentScenario::isB2EScenario($document->scenario)
			? ActionDictionary::ACTION_B2E_DOCUMENT_EDIT
			: ActionDictionary::ACTION_DOCUMENT_EDIT
		;
		$hasAccess = $this->accessController === null || $this->accessController->check($accessAction);
		if (!$hasAccess)
		{
			return $result->addError(new Main\Error("Has no access to change document"));
		}

		return $result;
	}
}

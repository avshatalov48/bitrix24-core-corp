<?php

namespace Bitrix\Sign\Controllers\V1\Document;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Attribute;
use Bitrix\Sign\Item\Api\Document\Signing\ResendMessageRequest;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Item\DocumentCollection;
use Bitrix\Sign\Item\MemberCollection;
use Bitrix\Sign\Service;
use Bitrix\Sign\Type\DocumentScenario;
use Bitrix\Sign\Type\DocumentStatus;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\MemberStatus;

class Send extends \Bitrix\Sign\Engine\Controller
{
	#[Attribute\ActionAccess(ActionDictionary::ACTION_DOCUMENT_EDIT)]
	public function getMembersForResendAction(array $memberIds): array
	{
		if (count($memberIds) === 0)
		{
			return ['readyMembers' => []];
		}

		$members = Service\Container::instance()
			->getMemberRepository()
			->listByIds($memberIds)
			->filterByStatus([
				MemberStatus::WAIT,
				MemberStatus::READY,
				MemberStatus::DONE,
			]);

		$documentIds = [];
		foreach ($members as $member)
		{
			$documentIds[$member->documentId] = true;
		}

		$documents = Service\Container::instance()
			->getDocumentRepository()
			->listByIds(array_keys($documentIds));

		return [
			'readyMembers' => self::getReadyForResendMembers($members, $documents),
		];
	}

	#[Attribute\ActionAccess(ActionDictionary::ACTION_DOCUMENT_EDIT)]
	public function resendMessageAction(array $memberIds): array
	{
		if (count($memberIds) === 0)
		{
			return ['readyMembers' => []];
		}

		$members = Service\Container::instance()
			->getMemberRepository()
			->listByIds($memberIds);

		$documentIds = [];
		foreach ($members as $member)
		{
			$documentIds[$member->documentId] = true;
		}

		$documents = Service\Container::instance()
			->getDocumentRepository()
			->listByIds(array_keys($documentIds));

		$readyMembers = self::getReadyForResendMembers($members, $documents);

		foreach ($members as $member)
		{
			if (!in_array($member->id, $readyMembers))
			{
				continue;
			}

			$document = $documents->getById($member->documentId);

			if (!$document)
			{
				continue;
			}

			$response = Service\Container::instance()->getApiDocumentSigningService()->resendMessage(
				new ResendMessageRequest($document->uid, $member->uid)
			);

			if (!$response->isSuccess())
			{
				$this->addErrors($response->getErrors());
				return [];
			}
		}

		return [];
	}

	/**
	 * @return array<int>
	 */
	private static function getReadyForResendMembers(MemberCollection $members, DocumentCollection $documents): array
	{
		$readyMembers = [];

		foreach ($members as $member)
		{
			$document = $documents->getById($member->documentId);

			if (!$document)
			{
				continue;
			}

			if (DocumentScenario::isB2EScenario($document->scenario))
			{
				continue;
			}

			if (
				self::isFirstPartyAndReadyToAcceptMessage($member, $document)
				|| self::isSecondPartyAndReadyToAcceptSigningMessage($member, $document)
				|| self::isSecondPartyAndReadyToAcceptFinalMessage($member, $document)
			)
			{
				$readyMembers[] = $member->id;
			}
		}

		return $readyMembers;
	}

	private static function isFirstPartyAndReadyToAcceptMessage(Member $member, Document $document): bool
	{
		return
			$member->party === 1
			&& in_array($member->status, [MemberStatus::WAIT, ...MemberStatus::getReadyForSigning()], true)
			&& $document->status === DocumentStatus::SIGNING
		;
	}

	private static function isSecondPartyAndReadyToAcceptSigningMessage(Member $member, Document $document): bool
	{
		if ($member->party !== 2)
		{
			return false;
		}

		if (!in_array($member->status, [MemberStatus::WAIT, ...MemberStatus::getReadyForSigning()], true))
		{
			return false;
		}

		$firstParty = Service\Container::instance()
			->getMemberRepository()
			->getByPartyAndDocumentId($document->id, 1)
		;
		return $firstParty && $firstParty->status  === MemberStatus::DONE;
	}

	private static function isSecondPartyAndReadyToAcceptFinalMessage(Member $member, Document $document): bool
	{
		return
			$member->party === 2
			&& $member->status === MemberStatus::DONE
			&& $document->status === DocumentStatus::DONE
		;
	}
}

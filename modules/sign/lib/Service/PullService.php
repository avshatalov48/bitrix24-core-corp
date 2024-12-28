<?php

namespace Bitrix\Sign\Service;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Pull\Event;
use Bitrix\Sign\Item;
use Bitrix\Main;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Service\Sign\UrlGeneratorService;
use Bitrix\Sign\Type\MemberStatus;
use CPullWatch;

class PullService
{
	private const FILTER_COUNTER_TAG = 'SIGN_CALLBACK_MEMBER_STATUS_CHANGED';
	private const COMMAND_MEMBER_STATUS_CHANGED = 'memberStatusChanged';
	private const MEMBER_INVITED_TO_SIGN = 'memberInvitedToSign';

	private UrlGeneratorService $urlGeneratorService;
	private readonly MemberService $memberService;

	public function __construct(
		?UrlGeneratorService $urlGeneratorService = null,
		?MemberService $memberService = null
	)
	{
		$this->urlGeneratorService = $urlGeneratorService ?? Container::instance()->getUrlGeneratorService();
		$this->memberService = $memberService ?? Container::instance()->getMemberService();
	}

	public function sendMemberStatusChanged(Item\Document $document, Item\Member $member): bool
	{
		try
		{
			$this->sendEventByTag(
				self::FILTER_COUNTER_TAG,
				self::COMMAND_MEMBER_STATUS_CHANGED,
				[
					'documentId' => $document->id,
					'memberId' => $member->id,
					'labelId' => 'sign_document_grid_label_id_' . $member->id,
					'isMemberReadyStatus' => MemberStatus::isReadyForSigning($member->status),
				],
			);
		}
		catch (Main\LoaderException)
		{
			return false;
		}

		return true;
	}

	/**
	 * @param array<int>|int $userIds
	 * @return bool
	 */
	public function sendUpdateMyDocumentGrid(array|int $userIds): bool
	{
		return $this->sendEventToUsers(
			$userIds,
			'updateMyDocumentGrid',
			[],
		);
	}

	public function sendCounterEvent(int $userId, string $eventName, int $count): bool
	{
		return $this->sendEventToUsers(
			$userId,
			$eventName,
			[
				'needActionCount' => $count,
			],
		);
	}

	public function sendMemberInvitedToSign(Item\Document $document, Item\Member $member): bool
	{
		$userId = $this->memberService->getUserIdForMember($member, $document);
		if ($userId === null)
		{
			return true;
		}

		return $this->sendEventToUsers(
			$userId,
			self::MEMBER_INVITED_TO_SIGN,
			[
				'documentUid' => $document->uid,
				'documentId' => $document->id,
				'member' => [
					'id' => $member->id,
					'uid' => $member->uid,
				],
				'signingLink' => $this->urlGeneratorService->makeSigningUrl($member),
			],
		);
	}

	/**
	 * @throws LoaderException
	 */
	private function sendEventByTag(string $tag, string $command, array $params): void
	{
		if (!Loader::includeModule('pull'))
		{
			return;
		}

		CPullWatch::AddToStack(
			$tag,
			[
				'module_id' => 'sign',
				'command' => $command,
				'params' => $params,
			]
		);
	}

	/**
	 * @param array<int>|int $userId
	 */
	public function sendEventToUsers(array|int $userIds, string $command, array $params): bool
	{
		if (!Loader::includeModule('pull'))
		{
			return false;
		}

		$userIds = is_array($userIds) ? $userIds : [$userIds];
		if (empty($userIds))
		{
			return true;
		}

		return Event::add(
			$userIds,
			[
				'module_id' => 'sign',
				'command' => $command,
				'params' => $params,
			],
		);
	}
}

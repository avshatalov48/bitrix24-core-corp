<?php

namespace Bitrix\Sign\Service;

use Bitrix\Main\LoaderException;
use Bitrix\Pull\Event;
use Bitrix\Sign\Item;
use Bitrix\Main;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Service\Sign\UrlGeneratorService;
use CPullWatch;

final class PullService
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
					'isMemberReadyStatus' => \Bitrix\Sign\Type\MemberStatus::isReadyForSigning($member->status),
				],
			);
		}
		catch (Main\LoaderException)
		{
			return false;
		}

		return true;
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
		if (!Main\Loader::includeModule('pull'))
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
	public function sendEventToUsers(array|int $userIds, string $command , array $params,): bool
	{
		$userIds = is_array($userIds) ? $userIds : [$userIds];

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

<?php

namespace Bitrix\Sign\Operation\Member\Reminder;

use Bitrix\Main;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Integration\Im\NotificationService;
use Bitrix\Sign\Service\Integration\Imbot\HrBot;
use Bitrix\Sign\Service\Sign\DocumentService;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Service\Sign\UrlGeneratorService;
use Bitrix\Sign\Service\UserService;
use Bitrix\Sign\Type\DateTime;
use Bitrix\Sign\Type\DocumentStatus;
use Bitrix\Sign\Type\Im\Notification\NotificationType;
use Bitrix\Sign\Type\MemberStatus;

final class Send implements Contract\Operation
{
	private readonly MemberRepository $memberRepository;
	private readonly NotificationService $imNotificationService;
	private readonly MemberService $memberService;
	private readonly DocumentService $documentService;
	private readonly UserService $userService;
	private readonly UrlGeneratorService $urlgeneratorService;

	public function __construct(
		private readonly Item\Document $document,
		private readonly int $memberLimit,
		private readonly DateTime $currentDateTime = new DateTime(),
		?MemberRepository $memberRepository = null,
		?NotificationService $imNotificationService = null,
	)
	{
		$this->memberRepository = $memberRepository ?? Container::instance()->getMemberRepository();
		$this->imNotificationService = $imNotificationService ?? Container::instance()->getImNotificationService();
		$this->memberService = Container::instance()->getMemberService();
		$this->documentService = Container::instance()->getDocumentService();
		$this->userService = Container::instance()->getUserService();
		$this->urlgeneratorService = Container::instance()->getUrlGeneratorService();
	}

	public function launch(): Main\Result
	{
		$document = $this->document;
		if ($document->id === null)
		{
			return (new Main\Result())->addError(new Main\Error('Document ID is not set'));
		}
		if (!$this->imNotificationService->isAvailable())
		{
			return (new Main\Result())->addError(new Main\Error('Notification service is not available'));
		}
		if (DocumentStatus::isFinalByDocument($document))
		{
			return new Main\Result();
		}

		$members = $this->listMembersToSendReminder($document);
		$result = new Main\Result();
		foreach ($members as $member)
		{
			$sendReminderResult = $this->sendReminder($document, $member);
			$result->addErrors($sendReminderResult->getErrors());
		}

		return $result;
	}

	private function sendReminder(Item\Document $document, Item\Member $member): Main\Result
	{
		$memberUserId = $this->memberService->getUserIdForMember($member, $document);
		if ($memberUserId === null)
		{
			return (new Main\Result())->addError(new Main\Error('Member user ID is not set'));
		}
		$botUserId = (new HrBot())->getBotUserId() ?? 0;

		$signingLink = $this->urlgeneratorService->makeSigningUrl($member);
		$documentTitle = $this->documentService->getComposedTitleByDocument($document);
		$message = Main\Localization\Loc::getMessage(
			'SIGN_OPERATION_MEMBER_REMINDER_SEND_MESSAGE_TEXT',
			['#DOCUMENT_TITLE_WITH_LINK#' => "<a href=\"$signingLink\">$documentTitle</a>"],
			$this->userService->getUserLanguage($memberUserId)
		);
		if ($message === null)
		{
			return (new Main\Result())->addError(new Main\Error("Failed to compose message for member: {$member->id}"));
		}

		$result = $this->imNotificationService->sendNotification(
			new Item\Integration\Im\Notification\Message(
				fromUserId: $botUserId,
				toUserId: $memberUserId,
				type: NotificationType::SYSTEM,
				title: null,
				message: $message,
			),
		);
		if (!$result->isSuccess())
		{
			return $result;
		}
		$member->reminder->lastSendDate = $this->currentDateTime->clone();

		return $this->memberRepository->update($member);
	}

	private function listMembersToSendReminder(Item\Document $document): Item\MemberCollection
	{
		$filter = Main\ORM\Query\Query::filter()
			->where(
				Main\ORM\Query\Query::filter()
					->logic('or')
					->whereColumn('REMINDER_PLANNED_NEXT_SEND_DATE', '>', 'REMINDER_LAST_SEND_DATE')
					->whereNull('REMINDER_LAST_SEND_DATE')
			)
			->whereNotNull('REMINDER_PLANNED_NEXT_SEND_DATE')
			->where('REMINDER_PLANNED_NEXT_SEND_DATE', '<=', $this->currentDateTime)
			->where('REMINDER_COMPLETED', false)
		;

		return $this->memberRepository->listByDocumentIdAndMemberStatusesAndCustomFilter(
			$document->id,
			MemberStatus::getReadyForSigning(),
			$filter,
			$this->memberLimit,
		);
	}
}

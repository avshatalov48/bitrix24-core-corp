<?php

namespace Bitrix\Sign\Operation\Member\Reminder;

use Bitrix\Main;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Item;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Member\Notification\ReminderType;
use Bitrix\Sign\Type\Member\Role;

final class Set implements Operation
{
	private readonly MemberRepository $memberRepository;

	public function __construct(
		private readonly Item\Document $document,
		private readonly string $memberRole,
		private readonly ReminderType $reminderType,
		?MemberRepository $memberRepository = null,
	)
	{
		$container = Container::instance();

		$this->memberRepository = $memberRepository ?? $container->getMemberRepository();
	}

	public function launch(): Main\Result
	{
		$document = $this->document;
		if ($document->id === null)
		{
			return (new Main\Result())->addError(new Main\Error('Document ID is not set'));
		}
		if (!Role::isValid($this->memberRole))
		{
			return (new Main\Result())->addError(new Main\Error('Member role is not valid'));
		}

		$result = $this->memberRepository->updateMembersReminderTypeByRole(
			$document->id,
			$this->memberRole,
			$this->reminderType
		);

		return $result;
	}
}
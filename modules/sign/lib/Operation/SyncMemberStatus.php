<?php

namespace Bitrix\Sign\Operation;

use Bitrix\Main;
use Bitrix\Sign\Callback\Messages\Member\MemberStatusChanged;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Result\Operation\MemberWebStatusResult;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Counter\B2e\UserToSignDocumentCounterService;
use Bitrix\Sign\Type;

class SyncMemberStatus implements Operation
{
	private readonly UserToSignDocumentCounterService $b2eUserToSignDocumentCounterService;

	public function __construct(
		private readonly Member $member,
		private readonly Document $document,
		private readonly ?MemberStatusChanged $message = null
	)
	{
		$this->b2eUserToSignDocumentCounterService = Container::instance()->getB2eUserToSignDocumentCounterService();
	}

	public function launch(): Main\Result|MemberWebStatusResult
	{
		$memberWebStatusResult = (new GetMemberWebStatus($this->member->uid, $this->document->uid))->launch();
		if (!$memberWebStatusResult->isSuccess())
		{
			return (new Main\Result())->addErrors($memberWebStatusResult->getErrors());
		}

		/** @var MemberWebStatusResult $result */

		$operation = (new ChangeMemberStatus(
			$this->member,
			$this->document,
			$memberWebStatusResult->status,
		));
		if ($this->message !== null)
		{
			$operation->setMessage($this->message);
		}
		$result = $operation->launch();

		if (!$result->isSuccess())
		{
			$errors = array_filter(
				$result->getErrors(),
				static fn(Main\Error $error): bool => $error->getCode() === ChangeMemberStatus::MEMBER_STATUS_ALREADY_SET_ERROR_CODE
			);

			if (empty($errors))
			{
				return $result;
			}
		}

		if (Type\DocumentScenario::isB2EScenario($this->document->scenario))
		{
			$this->b2eUserToSignDocumentCounterService->updateByMember($this->member);
		}

		return $memberWebStatusResult;
	}
}
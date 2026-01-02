<?php

namespace Bitrix\Sign\Operation\Document\Template;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\DocumentCollection;
use Bitrix\Sign\Item\Hr\EntitySelector\EntityCollection;
use Bitrix\Sign\Item\Member\SelectorEntityCollection;
use Bitrix\Sign\Item\MemberCollection;
use Bitrix\Sign\Operation\Member\GetMembersFromUserPartyEntities;
use Bitrix\Sign\Result\Operation\Document\Template\SetupDocumentSignersResult;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\MemberService;

class SetupDocumentsSigners implements Operation
{
	private readonly MemberService $memberService;

	public function __construct(
		private readonly DocumentCollection $documents,
		private readonly SelectorEntityCollection $signers,
		private readonly int $sendFromUserId,
		private readonly bool $excludeRejected = true,
	)
	{
		$this->memberService = Container::instance()->getMemberService();
	}

	public function launch(): Main\Result|SetupDocumentSignersResult
	{
		$result = (new GetMembersFromUserPartyEntities($this->signers, excludeRejectedSigners: $this->excludeRejected))->launch();
		$members = $result->members;
		$departments = $result->departments;

		foreach ($this->documents as $document)
		{
			$result = $this->setupDocument($document, $members, $departments);
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		return new SetupDocumentSignersResult(
			shouldCheckDepartmentSync: !empty($departments->count()),
		);
	}

	private function setupDocument(
		Document $document,
		MemberCollection $members,
		EntityCollection $departments,
	): Main\Result
	{
		$operation = new SetupTemplateMembers(
			document: $document,
			sendFromUserId: $this->sendFromUserId,
			memberList: $members->cloneMembers(),
			excludeRejected: $this->excludeRejected,
		);

		$result = $operation->launch();
		if (!$result->isSuccess())
		{
			return $result;
		}

		if ($departments->count() && Loader::includeModule('humanresources'))
		{
			$result = $this->memberService->prepareDepartmentsForSync($document->uid, $departments);
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		return new Main\Result();
	}
}
<?php

namespace Bitrix\Sign\Service\Counter\B2e;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\DocumentScenario;
use Bitrix\Sign\Type\Member\EntityType;
use Bitrix\Sign\Type\Member\Role;
use CUserCounter;

final class UserToSignDocumentCounterService
{
	public const CODE = 'sign_b2e_current';
	private ?Document $document = null;

	public function __construct(private ?MemberRepository $memberRepository = null, private ?DocumentRepository $documentRepository = null)
	{
		$this->memberRepository ??= Container::instance()->getMemberRepository();
		$this->documentRepository ??= Container::instance()->getDocumentRepository();
	}

	public function updateByDocument(Document $document): Result
	{
		$result = new Result();
		$documentId = $document->id;
		if($documentId === null)
		{
			return $result->addError(new Error('Document id is empty.'));
		}

		$b2eDocument = $this->getDocumentById($documentId);
		if ($b2eDocument === null)
		{
			return $result->addError(new Error('Document not found.'));
		}

		if (!DocumentScenario::isB2EScenario($b2eDocument->scenario))
		{
			return $result->addError(new Error('Document type is not supported.'));
		}

		$members = $this->memberRepository->listByDocumentId($documentId);

		foreach ($members as $member)
		{
			if($member)
			{
				$this->updateByMember($member);
			}
		}

		return $result;
	}

	public function updateByMember(Member $member): Result
	{
		$result = new Result();

		$userId = Container::instance()->getMemberService()->getUserIdForMember($member);

		if ($userId !== null)
		{
			$this->update($userId);
		}
		else
		{
			$result->addError(new Error('User not found.'));
		}

		return $result;
	}

	public function update(?int $userId = null): void
	{
		$userId ??= CurrentUser::get()->getId();
		$count = $this->memberRepository->getCountForCurrentUserAction($userId);
		$this->set($count, $userId);

		\Bitrix\Main\Loader::includeModule('pull');
		\Bitrix\Pull\Event::add(
			[$userId],
			[
				'module_id' => 'sign',
				'command' => 'changeB2eCurrentCounters',
				'params' => [],
			]
		);
	}

	private function getDocumentById(int $documentId): ?Document
	{
		if ($this->document === null)
		{
			$this->document = $this->documentRepository?->getById($documentId);
		}

		return $this->document;
	}

	public function getCounterId(): string
	{
		return self::CODE;
	}

	public function get(?int $userId = null): int
	{
		$userId ??= CurrentUser::get()->getId();

		return ($userId !== null) ? (int)CUserCounter::GetValue($userId, self::CODE, '**') : 0;
	}

	public function set(int $value, ?int $userId = null): void
	{
		$userId ??= CurrentUser::get()->getId();
		if ($userId !== null)
		{
			CUserCounter::Set($userId, self::CODE, $value, '**');
		}
	}

	public function clear(?int $userId = null): void
	{
		$userId ??= CurrentUser::get()->getId();
		if ($userId !== null)
		{
			CUserCounter::Clear($userId, self::CODE, '**');
		}
	}
}

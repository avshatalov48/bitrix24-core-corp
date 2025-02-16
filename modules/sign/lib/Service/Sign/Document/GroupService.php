<?php

namespace Bitrix\Sign\Service\Sign\Document;

use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Sign\Item\Document\Group;
use Bitrix\Sign\Item\DocumentCollection;
use Bitrix\Sign\Repository\Document\GroupRepository;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Result\Service\Sign\Document\CreateGroupResult;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\DocumentScenario;

final class GroupService
{
	public const MAX_DOCUMENT_COUNT = 20;
	private readonly GroupRepository $groupRepository;
	private readonly DocumentRepository $documentRepository;

	public function __construct()
	{
		$container = Container::instance();
		$this->groupRepository = $container->getDocumentGroupRepository();
		$this->documentRepository = $container->getDocumentRepository();
	}

	public function create(int $userId): Result|CreateGroupResult
	{
		if ($userId < 1)
		{
			return (new Result())->addError(new Error('Invalid user id'));
		}

		$item = new Group($userId);
		$result = $this->groupRepository->add($item);
		if (!$result->isSuccess())
		{
			return $result;
		}

		return new CreateGroupResult($item);
	}

	public function attach(string $documentUid, int $groupId): Result
	{
		$result = new Result();

		if ($groupId < 1)
		{
			return (new Result())->addError(new Error('Invalid group id'));
		}

		if ($this->groupRepository->getById($groupId) === null)
		{
			return $result->addError(new Error('Group not found'));
		}

		$groupDocumentCount = $this->documentRepository->getCountByGroupId($groupId);
		if ($groupDocumentCount >= self::MAX_DOCUMENT_COUNT)
		{
			return $result->addError(new Error('Group already has too many documents', 'MAX_DOCUMENT_COUNT_EXCEEDED'));
		}

		$document = $this->documentRepository->getByUid($documentUid);
		if (!$document)
		{
			return $result->addError(new Error('Document not found'));
		}

		if (!DocumentScenario::isB2EScenario($document->scenario))
		{
			return $result->addError(new Error('Document type is not supported.'));
		}

		if ($document->groupId !== null)
		{
			return $result->addError(new Error('Document group is already attached'));
		}

		$document->groupId = $groupId;

		return $this->documentRepository->update($document);
	}

	public function detach(string $documentUid): Result
	{
		$result = new Result();

		$document = $this->documentRepository->getByUid($documentUid);
		if (!$document)
		{
			return $result->addError(new Error('Document not found'));
		}

		if (!DocumentScenario::isB2EScenario($document->scenario))
		{
			return $result->addError(new Error('Document type is not supported.'));
		}

		if ($document->groupId === null)
		{
			return $result->addError(new Error('Document group is not attached'));
		}

		$groupId = $document->groupId;
		if ($this->groupRepository->getById($groupId) === null)
		{
			return $result->addError(new Error('Group not found'));
		}

		$document->groupId = null;

		return $this->documentRepository->update($document);
	}

	public function getDocumentList(int $groupId): DocumentCollection
	{
		if ($groupId < 1)
		{
			return new DocumentCollection();
		}

		return $this->documentRepository->listByGroupId($groupId, self::MAX_DOCUMENT_COUNT);
	}
}

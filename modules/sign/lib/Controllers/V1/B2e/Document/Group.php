<?php

namespace Bitrix\Sign\Controllers\V1\B2e\Document;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Sign\Access\AccessController;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Attribute;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Engine\Controller;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Result\Service\Sign\Document\CreateGroupResult;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Access\AccessibleItemType;
use Bitrix\Sign\Operation;

final class Group extends Controller
{
	private ?AccessController $accessController = null;

	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_ADD),
		new Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT),
	)]
	public function createAction(): array
	{
		if (!Storage::instance()->isB2eAvailable())
		{
			$this->addError(new Error('B2E is not available'));

			return [];
		}

		$currentUserId = (int)CurrentUser::get()->getId();
		$createResult = Container::instance()->getDocumentGroupService()->create($currentUserId);
		if (!$createResult instanceof CreateGroupResult)
		{
			$this->addError(new Error('Can\'t create document group'));
			$this->addErrors($createResult->getErrors());

			return [];
		}

		return [
			'groupId' => $createResult->group->id,
		];
	}

	#[Attribute\ActionAccess(
		permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		itemType: AccessibleItemType::DOCUMENT,
		itemIdOrUidRequestKey: 'documentUid',
	)]
	public function attachAction(string $documentUid, int $groupId): array
	{
		if (!Storage::instance()->isB2eAvailable())
		{
			$this->addError(new Error('B2E is not available'));

			return [];
		}

		$attachResult = Container::instance()->getDocumentGroupService()->attach($documentUid, $groupId);
		if (!$attachResult->isSuccess())
		{
			$this->addErrors($attachResult->getErrors());

			return [];
		}

		return [];
	}

	#[Attribute\ActionAccess(
		permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		itemType: AccessibleItemType::DOCUMENT,
		itemIdOrUidRequestKey: 'documentUid',
	)]
	public function detachAction(string $documentUid): array
	{
		if (!Storage::instance()->isB2eAvailable())
		{
			$this->addError(new Error('B2E is not available'));

			return [];
		}

		$detachResult = Container::instance()->getDocumentGroupService()->detach($documentUid);
		if (!$detachResult->isSuccess())
		{
			$this->addErrors($detachResult->getErrors());

			return [];
		}

		return [];
	}

	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_ADD),
		new Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT),
	)]
	public function documentListAction(int $groupId): array
	{
		if (!Storage::instance()->isB2eAvailable())
		{
			$this->addError(new Error('B2E is not available'));

			return [];
		}

		if ($groupId < 1)
		{
			$this->addError(new Error('Invalid group id'));

			return [];
		}

		$documentList = Container::instance()->getDocumentGroupService()->getDocumentList($groupId);

		return $documentList->toArray();
	}

	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_ADD),
		new Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT),
	)]
	public function configureAction(int $groupId): array
	{
		$this->processDocumentList(
			$groupId,
			function (string $uid): void
			{
				$result = (new Operation\ConfigureFillAndStart($uid))->launch();
				$this->addErrorsFromResult($result);
				if ($result instanceof Operation\Result\ConfigureResult && !$result->completed)
				{
					Container::instance()
						->getDocumentAgentService()
						->addConfigureAndStartAgent($uid)
					;
				}
			},
		);

		return [];
	}

	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_ADD),
		new Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT),
	)]
	public function getFillAndStartProgressAction(int $groupId): array
	{
		$completedCount = 0;
		$totalCount = 0;
		$result = $this->processDocumentList(
			$groupId,
			function (string $uid, int $key) use (&$completedCount, &$totalCount): void
			{
				$result = (new Operation\GetFillAndStartProgress($uid))->launch();
				$this->addErrorsFromResult($result);
				if ($result instanceof Operation\Result\ConfigureProgressResult && $result->completed)
				{
					$completedCount++;
				}

				$totalCount = $key + 1;
			},
		);

		if (!$result)
		{
			return [];
		}

		$progress = $totalCount > 0 ? round(100 / $totalCount * $completedCount) : 0;

		return [
			'completed' => $totalCount === $completedCount,
			'progress' => $progress,
		];
	}

	private function processDocumentList(int $groupId, callable $callback): bool
	{
		if (!Storage::instance()->isB2eAvailable())
		{
			$this->addError(new Error('B2E is not available'));

			return false;
		}

		if ($groupId < 1)
		{
			$this->addError(new Error('Invalid group id'));

			return false;
		}

		$documentList = Container::instance()->getDocumentGroupService()->getDocumentList($groupId);
		foreach ($documentList as $key => $document)
		{
			if ($document === null)
			{
				$this->addError(new Error('Empty document'));

				continue;
			}

			if (!$this->checkDocumentAccessForCurrentUser($document))
			{
				$this->addError(new Error('Current user does not have access to the document'));

				continue;
			}

			$uid = $document->uid;
			if ($uid === null)
			{
				$this->addError(new Error('Invalid document'));

				continue;
			}

			$callback($uid, $key);
		}

		return true;
	}

	private function checkDocumentAccessForCurrentUser(Document $document): bool
	{
		if ($this->accessController === null)
		{
			$userId = CurrentUser::get()->getId();
			$this->accessController = new AccessController($userId);
		}

		$item = Container::instance()->getAccessibleItemFactory()->createFromItem($document);

		return $this->accessController->check(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT, $item);
	}
}

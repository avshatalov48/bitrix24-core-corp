<?php

namespace Bitrix\Sign\Controllers\V1\Document;

use Bitrix\Main;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Attribute;
use Bitrix\Sign\Debug\Logger;
use Bitrix\Sign\Item\Hr\EntitySelector\Entity;
use Bitrix\Sign\Item\Hr\EntitySelector\EntityCollection;
use Bitrix\Sign\Item\Hr\NodeSync;
use Bitrix\Sign\Item\MemberCollection;
use Bitrix\Sign\Operation\Member\SyncDepartmentsPage;
use Bitrix\Sign\Service;
use Bitrix\Sign\Type\Access\AccessibleItemType;
use Bitrix\Sign\Type\Hr\EntitySelector;
use Bitrix\Sign\Type\Member\EntityType;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\MemberStatus;
use Bitrix\Sign\Ui\Member\Stage;

class Member extends \Bitrix\Sign\Engine\Controller
{
	private Service\Sign\MemberService $memberService;
	private Service\Sign\DocumentService $documentService;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);
		$this->memberService = Service\Container::instance()->getMemberService();
		$this->documentService = Service\Container::instance()->getDocumentService();
	}

	/**
	 * @param string $documentUid
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'documentUid',
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'documentUid',
		)
	)]
	public function loadAction(string $documentUid): array
	{
		$document = Service\Container::instance()
			->getDocumentRepository()
			->getByUid($documentUid)
		;

		if (!$document)
		{
			$this->addError(new Error(Loc::getMessage('SIGN_CONTROLLER_MEMBER_DOCUMENT_NOT_FOUND')));

			return [];
		}

		$members = Service\Container::instance()
			->getMemberRepository()
			->listByDocumentId($document->id)
		;
		$result = [];
		$isCrmModuleIncluded = Loader::includeModule('crm');
		foreach ($members as $member)
		{
			$entityTypeId = null;
			if ($isCrmModuleIncluded)
			{
				$entityTypeId = match ($member->entityType)
				{
					EntityType::CONTACT => \CCrmOwnerType::Contact,
					EntityType::COMPANY => \CCrmOwnerType::Company,
					default => null,
				};
			}

			$result[] = [
				'uid' => $member->uid,
				'entityId' => $member->entityId,
				'entityType' => $member->entityType,
				'entityTypeId' => $entityTypeId,
				'presetId' => $member->presetId,
				'party' => $member->party,
				'role' => $member->role,
			];
		}

		return $result;
	}

	/**
	 * @param string $documentUid
	 * @param string $entityType
	 * @param int $entityId
	 * @param int $party
	 * @param int $presetId
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'documentUid',
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'documentUid',
		),
	)]
	public function addAction(
		string $documentUid,
		string $entityType,
		int $entityId,
		int $party,
		int $presetId = 0,
	): array
	{
		$addResult = $this->memberService->addForDocument($documentUid, $entityType, $entityId, $party, $presetId);

		if (!$addResult->isSuccess())
		{
			$this->addErrors($addResult->getErrors());

			return [];
		}

		return [
			'uid' => $addResult->getData()['member']->uid,
		];
	}

	/**
	 * @param string $uid
	 *
	 * @return array
	 */
	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(
			ActionDictionary::ACTION_DOCUMENT_EDIT,
			AccessibleItemType::DOCUMENT,
			'uid',
	),
		new Attribute\ActionAccess(
			ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			AccessibleItemType::DOCUMENT,
			'uid'
		)
	)]
	public function removeAction(string $uid)
	{
		$removeResult = $this->memberService->remove($uid);

		if (!$removeResult->isSuccess())
		{
			$this->addErrors($removeResult->getErrors());

			return [];
		}

		return [];
	}

	/**
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'documentUid',
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'documentUid',
		),
	)]
	public function removeByPartAction(string $documentUid, string $entityType, int $entityId, int $party): array
	{
		$removeResult = $this->memberService->removeFromDocumentAndPart($documentUid, $entityType, $entityId, $party);

		if (!$removeResult->isSuccess())
		{
			$this->addErrors($removeResult->getErrors());

			return [];
		}

		return [];
	}

	#[Attribute\ActionAccess(
		ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		itemType: AccessibleItemType::DOCUMENT,
		itemIdOrUidRequestKey: 'documentUid',
	)]
	public function getDepartmentsForDocumentAction(
		string $documentUid,
		int $page = 1,
		int $pageSize = 19,
	): array
	{
		if (!Loader::includeModule('humanresources'))
		{
			$this->addError(new Error('Module humanresources is not available'));

			return [];
		}

		$document = Service\Container::instance()->getDocumentService()->getByUid($documentUid);

		if (!$document)
		{
			$this->addError(new Error(Loc::getMessage('SIGN_CONTROLLER_MEMBER_DOCUMENT_NOT_FOUND')));

			return [];
		}

		[$limit, $offset] = \Bitrix\Sign\Util\Query\Db\Paginator::getLimitAndOffset($pageSize, $page);

		$documentNodes = Service\Container::instance()
			->getMemberNodeRepository()
			->getNodesForDocument($document->id, $limit, $offset)
		;

		$departments = [];
		/** @var NodeSync $node */
		foreach ($documentNodes as $node)
		{
			$nodeInfo = \Bitrix\HumanResources\Service\Container::instance()->getNodeService()->getNodeInformation($node->nodeId);

			if (!$nodeInfo)
			{
				$this->addError(new Error('node info error'));

				return [];
			}

			$departments[] = [
				'id' => $nodeInfo->id,
				'name' => $node->isFlat
					? Loc::getMessage('SIGN_CONTROLLER_MEMBER_FLAT_DEPARTMENT', [
						'#DEPARTMENT_NAME#' => $nodeInfo->name,
					])
					: $nodeInfo->name
				,
			];
		}

		return [
			'departments' => $departments,
		];
	}

	#[Attribute\ActionAccess(
		ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		itemType: AccessibleItemType::DOCUMENT,
		itemIdOrUidRequestKey: 'documentUid',
	)]
	public function getMembersForDocumentAction(
		string $documentUid,
		int $page = 1,
		int $pageSize = 20,
	): array
	{
		$document = Service\Container::instance()->getDocumentService()->getByUid($documentUid);

		if (!$document)
		{
			$this->addError(new Error(Loc::getMessage('SIGN_CONTROLLER_MEMBER_DOCUMENT_NOT_FOUND')));

			return [];
		}

		[$limit, $offset] = \Bitrix\Sign\Util\Query\Db\Paginator::getLimitAndOffset($pageSize, $page);

		$memberCollection = Service\Container::instance()->getMemberRepository()->listByDocumentIdWithRole($document->id, Role::SIGNER, $limit, $offset)->toArray();

		$members = [];
		foreach ($memberCollection as $member)
		{
			$avatar = Service\Container::instance()->getSignMemberUserService()->getAvatarByMemberUid($member->uid);
			$userId = Service\Container::instance()->getMemberService()->getUserIdForMember($member, $document);
			$members[] = [
				'memberId' => $member->id,
				'userId' => $userId,
				'name' => $member->name,
				'avatar' => $avatar?->getBase64Content(),
				'profileUrl' => $userId ? '/company/personal/user/' . $userId . '/' : '',
			];
		}

		return [
			'members' => $members,
		];
	}

	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT),
		new Attribute\ActionAccess(ActionDictionary::ACTION_B2E_TEMPLATE_EDIT),
	)]
	public function getUniqSignersCountAction(
		array $members,
	): array
	{
		$entityCollection = new EntityCollection();
		foreach ($members as $member)
		{
			if (!isset($member['entityType'], $member['entityId']))
			{
				$this->addError(new Error('Invalid member data'));

				return [];
			}

			$entityCollection->add(
				\Bitrix\Sign\Item\Hr\EntitySelector\Entity::createFromStrings(
					entityId: $member['entityId'],
					entityType: $member['entityType'],
				),
			);
		}

		$result = $this->memberService->getUniqueSignersCount($entityCollection);

		if (!$result->isSuccess())
		{
			Logger::getInstance()->error($result->getError());
			$this->addErrorByMessage('Error while getting unique signers count');

			return [];
		}

		return [
			'count' => $result->getData()['count'],
		];
	}

	#[Attribute\ActionAccess(
		ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		itemType: AccessibleItemType::DOCUMENT,
		itemIdOrUidRequestKey: 'documentUid',
	)]
	public function getUniqSignersCountForDocumentAction(
		string $documentUid,
	): array
	{
		$document = Service\Container::instance()->getDocumentService()->getByUid($documentUid);

		if (!$document)
		{
			$this->addError(new Error(Loc::getMessage('SIGN_CONTROLLER_MEMBER_DOCUMENT_NOT_FOUND')));

			return [];
		}

		$memberCollection = Service\Container::instance()
			->getMemberRepository()
			->listByDocumentIdWithRole($document->id, Role::SIGNER)
		;

		$entityCollection = EntityCollection::fromMemberCollection($memberCollection);

		$result = $this->memberService->getUniqueSignersCount($entityCollection);

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return [];
		}

		return [
			'count' => $result->getData()['count'],
		];
	}

	#[Attribute\ActionAccess(
		ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		itemType: AccessibleItemType::DOCUMENT,
		itemIdOrUidRequestKey: 'documentUid',
	)]
	public function syncB2eMembersWithDepartmentsAction(
		string $documentUid,
		int $currentParty,
	): array
	{
		if (!$this->getSyncMembersLock($documentUid))
		{
			return ['syncFinished' => false];
		}

		if (!Main\Loader::includeModule('humanresources'))
		{
			$this->addError(new Main\Error('Module humanresources is not available'));

			return [];
		}

		$document = $this->documentService->getByUid($documentUid);

		if (!$document)
		{
			$this->addError(new Main\Error(Loc::getMessage('SIGN_SERVICE_MEMBER_DOCUMENT_NOT_FOUND')));

			return [];
		}

		$nodeMemberService = \Bitrix\HumanResources\Service\Container::instance()->getNodeMemberService();
		$result = (new SyncDepartmentsPage($document, $currentParty, $nodeMemberService))->launch();

		if (!$result->isSuccess())
		{
			Logger::getInstance()->error($result->getError());
			$this->addErrorByMessage('Error while syncing departments');

			return [];
		}

		if ($result->isSuccess())
		{
			return [
				'syncFinished' => $result->getData()['syncFinished'] ?? false,
			];
		}

		$this->releaseSyncMembersLock($documentUid);

		return [
			'syncFinished' => true,
		];
	}

	/**
	 * @param string $documentUid
	 * @param int $representativeId
	 * @param array $members Members data [[entityId, entityType, party], ...]
	 * @return array
	 */
	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(ActionDictionary::ACTION_DOCUMENT_EDIT, AccessibleItemType::DOCUMENT, 'documentUid'),
		new Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT, AccessibleItemType::DOCUMENT, 'documentUid')
	)]
	public function setupB2ePartiesAction(
		string $documentUid,
		int $representativeId,
		array $members,
	): array
	{
		if ($representativeId <= 0)
		{
			$this->addError(
				new Error("Invalid `representativeId` value"),
			);

			return [];
		}

		$requiredFields = ['entityType', 'entityId', 'party'];
		foreach ($members as $member)
		{
			foreach ($requiredFields as $requiredField)
			{
				if (!array_key_exists($requiredField, $member))
				{
					$this->addError(
						new Error("Not all members contains `{$requiredField}`"),
					);

					return [];
				}
			}

			// numeric or numeric:F (entity selector values)
			if (!preg_match('/^\d+$|^\d+:F$/', $member['entityId']))
			{
				$this->addError(
					new Error('Invalid `entityId` field value'),
				);

				return [];
			}

			if (!is_int($member['party']))
			{
				$this->addError(
					new Error('Invalid `party` field value'),
				);

				return [];
			}

			if (isset($member['role']))
			{
				if (!is_string($member['role']) || !Role::isValid($member['role']))
				{
					$this->addError(
						new Error('Invalid `role` field value'),
					);

					return [];
				}
			}
		}

		$memberCollection = new MemberCollection();
		$departmentEntities = new EntityCollection();
		foreach ($members as $member)
		{
			$entityType = EntitySelector\EntityType::fromEntityIdAndType(
				$member['entityId'],
				$member['entityType'],
			);

			if ($entityType->isDepartment())
			{
				$departmentEntities->add(Entity::createFromStrings(
					entityId: $member['entityId'],
					entityType: $member['entityType'],
				));
			}

			$party = (int)$member['party'];

			$role = $member['role'] ?? null;
			if ($role === null)
			{
				$role = \Bitrix\Sign\Compatibility\Role::createByParty($party);
			}

			$memberEntityType = $entityType === EntitySelector\EntityType::FlatDepartment
				? EntityType::DEPARTMENT_FLAT
				: $member['entityType'];

			$memberCollection->add(
				new \Bitrix\Sign\Item\Member(
					party: $party,
					entityType: $memberEntityType,
					entityId: (int)$member['entityId'],
					role: (string)$role,
				),
			);
		}

		$result = $this->memberService->setupB2eMembers($documentUid, $memberCollection, $representativeId);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return [];
		}

		if ($departmentEntities->count() && Loader::includeModule('humanresources'))
		{
			$result = $this->memberService->prepareDepartmentsForSync($documentUid, $departmentEntities);
			if (!$result->isSuccess())
			{
				$this->addErrors($result->getErrors());

				return [];
			}
		}

		$result = $this->documentService->modifyRepresentativeId($documentUid, $representativeId);
		if (!$result->isSuccess())
		{
			// Revert adding members
			$this->memberService->cleanByDocumentUid($documentUid);
			$this->addErrors($result->getErrors());

			return [];
		}

		return [];
	}

	/**
	 * @param string $documentUid
	 * @return array
	 */
	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(ActionDictionary::ACTION_DOCUMENT_EDIT, AccessibleItemType::DOCUMENT, 'documentUid'),
		new Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT, AccessibleItemType::DOCUMENT, 'documentUid')
	)]
	public function cleanAction(string $documentUid): array
	{
		$removeResult = $this->memberService->cleanByDocumentUid($documentUid);
		if (!$removeResult->isSuccess())
		{
			$this->addErrors($removeResult->getErrors());

			return [];
		}

		return [];
	}

	/**
	 * @param string $uid
	 * @param string $channelType
	 * @param string $channelValue
	 *
	 * @return array
	 */
	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(ActionDictionary::ACTION_DOCUMENT_EDIT, AccessibleItemType::DOCUMENT, 'uid'),
		new Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT, AccessibleItemType::DOCUMENT, 'uid')
	)]
	public function modifyCommunicationChannelAction(string $uid, string $channelType, string $channelValue): array
	{
		$modifyResult = $this->memberService->modifyCommunicationChannel(
			$uid,
			$channelType,
			$channelValue,
		);

		if (!$modifyResult->isSuccess())
		{
			$this->addErrors($modifyResult->getErrors());

			return [];
		}

		return [];
	}

	/**
	 * @param string $uid
	 *
	 * @return array
	 */
	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(ActionDictionary::ACTION_DOCUMENT_EDIT, AccessibleItemType::DOCUMENT, 'uid'),
		new Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT, AccessibleItemType::DOCUMENT, 'uid')
	)]
	public function loadCommunicationsAction(string $uid): array
	{
		$member = $this->memberService->getByUid($uid);

		if (!$member)
		{
			return [];
		}

		return $this->memberService->getCommunications($member);
	}

	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(ActionDictionary::ACTION_DOCUMENT_EDIT, AccessibleItemType::DOCUMENT, 'uid'),
		new Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT, AccessibleItemType::DOCUMENT, 'uid')
	)]
	public function loadAppliedCommunicationAction(string $uid): array
	{
		$member = $this->memberService->getByUid($uid);

		if (!$member || !$member->channelType)
		{
			return [];
		}

		return [
			'type' => $member->channelType,
			'value' => $member->channelValue,
		];
	}

	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(ActionDictionary::ACTION_DOCUMENT_EDIT, AccessibleItemType::DOCUMENT, 'uid'),
		new Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT, AccessibleItemType::DOCUMENT, 'uid')
	)]
	public function saveStampAction(string $memberUid, string $fileId): array
	{
		$fileController = new \Bitrix\Sign\Upload\StampUploadController();
		$uploader = new \Bitrix\UI\FileUploader\Uploader($fileController);
		$pendingFiles = $uploader->getPendingFiles([$fileId]);
		$file = $pendingFiles->get($fileId);

		$stampFileId = $file?->getFileId();
		if ($stampFileId === null)
		{
			$this->addError(new Error("File didnt loaded"));

			return [];
		}
		$member = $this->memberService->getByUid($memberUid);
		$result = $this->memberService->saveStampFile($stampFileId, $member);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return [];
		}
		$savedFileId = (int)$result->getData()['fileId'];

		return [
			'id' => $savedFileId,
			'srcUri' => \CFile::GetPath($stampFileId),
		];
	}

	public function loadStageAction($memberId): array
	{
		$container = Service\Container::instance();
		$memberRepository = $container->getMemberRepository();
		$member = $memberRepository->getById($memberId);
		if ($member === null)
		{
			return [];
		}

		$document = $container->getDocumentRepository()->getById($member->documentId);
		if ($document === null)
		{
			return [];
		}

		return Stage::createInstance($member, $document)->getInfo();
	}

	public function getAction(string $uid): array
	{
		$currentUserId = CurrentUser::get()->getId();
		$userService = $this->container->getSignMemberUserService();
		$member = $this->memberService->getByUid($uid);
		if (!$member)
		{
			$this->addError(new Error('Member not found'));

			return [];
		}
		if (!$userService->checkAccessToMember($member, $currentUserId))
		{
			$this->addError(new Error('Member not found'));

			return [];
		}

		return [
			'id' => $member->id,
			'uid' => $member->uid,
			'status' => MemberStatus::toPresentedView($member->status),
		];
	}

	private function getSyncMembersLock(string $docUid): bool
	{
		return Main\Application::getConnection()->lock($this->getSyncMembersLockName($docUid));
	}

	private function releaseSyncMembersLock(string $docUid): bool
	{
		return Main\Application::getConnection()->unlock($this->getSyncMembersLockName($docUid));
	}

	private function getSyncMembersLockName(string $docUid): string
	{
		return "sign_sync_members_{$docUid}";
	}
}

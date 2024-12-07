<?php

namespace Bitrix\Sign\Controllers\V1\Document;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Attribute;
use Bitrix\Sign\Item\MemberCollection;
use Bitrix\Sign\Service;
use Bitrix\Sign\Type\Access\AccessibleItemType;
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
	#[Attribute\ActionAccess(
		permission: ActionDictionary::ACTION_DOCUMENT_EDIT,
		itemType: AccessibleItemType::DOCUMENT,
		itemIdRequestKey: 'documentUid'
	)]
	#[Attribute\ActionAccess(
		permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		itemType: AccessibleItemType::DOCUMENT,
		itemIdRequestKey: 'documentUid'
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
	#[Attribute\ActionAccess(
		permission: ActionDictionary::ACTION_DOCUMENT_EDIT,
		itemType: AccessibleItemType::DOCUMENT,
		itemIdRequestKey: 'documentUid'
	)]
	#[Attribute\ActionAccess(
		permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		itemType: AccessibleItemType::DOCUMENT,
		itemIdRequestKey: 'documentUid'
	)]
	public function addAction(
		string $documentUid,
		string $entityType,
		int $entityId,
		int $party,
		int $presetId = 0
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
	#[Attribute\ActionAccess(ActionDictionary::ACTION_DOCUMENT_EDIT)]
	#[Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT)]
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
	#[Attribute\ActionAccess(
		permission: ActionDictionary::ACTION_DOCUMENT_EDIT,
		itemType: AccessibleItemType::DOCUMENT,
		itemIdRequestKey: 'documentUid'
	)]
	#[Attribute\ActionAccess(
		permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		itemType: AccessibleItemType::DOCUMENT,
		itemIdRequestKey: 'documentUid'
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

	/**
	 * @param string $documentUid
	 * @param int $representativeId
	 * @param array $members Members data [[entityId, entityType, party], ...]
	 * @return array
	 */
	#[Attribute\ActionAccess(ActionDictionary::ACTION_DOCUMENT_EDIT)]
	#[Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT)]
	public function setupB2ePartiesAction(
		string $documentUid,
		int $representativeId,
		array $members
	): array
	{
		if ($representativeId <= 0)
		{
			$this->addError(
				new Error("Invalid `representativeId` value")
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
						new Error("Not all members contains `${requiredField}`")
					);

					return [];
				}
			}

			if (!is_int($member['entityId']))
			{
				$this->addError(
					new Error('Invalid `entityId` field value')
				);

				return [];
			}

			if (!is_int($member['party']))
			{
				$this->addError(
					new Error('Invalid `party` field value')
				);

				return [];
			}

			if (isset($member['role']))
			{
				if (!is_string($member['role']) || !Role::isValid($member['role']))
				{
					$this->addError(
						new Error('Invalid `role` field value')
					);

					return [];
				}
			}
		}

		$memberCollection = new MemberCollection();
		foreach ($members as $member)
		{
			$party = (int)$member['party'];

			$role = $member['role'] ?? null;
			if ($role === null)
			{
				$role = \Bitrix\Sign\Compatibility\Role::createByParty($party);
			}

			$memberCollection->add(
				new \Bitrix\Sign\Item\Member(
					party: $party,
					entityType: $member['entityType'],
					entityId: (int)$member['entityId'],
					role: (string)$role,
				)
			);
		}

		$result = $this->memberService->setupB2eMembers($documentUid, $memberCollection, $representativeId);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return [];
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
	#[Attribute\ActionAccess(ActionDictionary::ACTION_DOCUMENT_EDIT)]
	#[Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT)]
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
	#[Attribute\ActionAccess(ActionDictionary::ACTION_DOCUMENT_EDIT)]
	#[Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT)]
	public function modifyCommunicationChannelAction(string $uid, string $channelType, string $channelValue): array
	{
		$modifyResult = $this->memberService->modifyCommunicationChannel(
			$uid,
			$channelType,
			$channelValue
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
	#[Attribute\ActionAccess(ActionDictionary::ACTION_DOCUMENT_EDIT)]
	#[Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT)]
	public function loadCommunicationsAction(string $uid): array
	{
		$member = $this->memberService->getByUid($uid);

		if (!$member)
		{
			return [];
		}

		return $this->memberService->getCommunications($member);
	}

	/**
	 * @param string $uid
	 *
	 * @return array
	 */
	#[Attribute\ActionAccess(ActionDictionary::ACTION_DOCUMENT_EDIT)]
	#[Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT)]
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

	#[Attribute\ActionAccess(ActionDictionary::ACTION_DOCUMENT_EDIT)]
	#[Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT)]
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
}
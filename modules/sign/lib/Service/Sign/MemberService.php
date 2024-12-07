<?php

namespace Bitrix\Sign\Service\Sign;

use Bitrix\Bizproc\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Connector\MemberDataPicker;
use Bitrix\Sign\File;
use Bitrix\Sign\Integration\Bitrix24\B2eTariff;
use Bitrix\Sign\Integration\CRM;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Repository\FileRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Main;
use Bitrix\Sign\Item;
use Bitrix\Sign\Service;
use Bitrix\Sign\Service\Sign\Member\CommunicationService;
use Bitrix\Sign\Type;
use Bitrix\Sign\Type\DocumentScenario;
use Bitrix\Sign\Type\Member\EntityType;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\MemberStatus;
use function Amp\Iterator\map;

class MemberService
{
	private MemberRepository $memberRepository;
	private DocumentRepository $documentRepository;
	private FileRepository $fileRepository;
	private Service\Integration\Crm\B2eDocumentService $b2eDocumentService;

	private const ALLOWED_ENTITY_TYPES = [
		EntityType::COMPANY,
		EntityType::CONTACT,
		EntityType::USER,
	];
	private const CHANNEL_TYPE_PHONE = Type\Member\ChannelType::PHONE;
	private const CHANNEL_TYPE_EMAIL = Type\Member\ChannelType::EMAIL;
	private const ALLOWED_CHANNEL_TYPES = [
		self::CHANNEL_TYPE_PHONE,
		self::CHANNEL_TYPE_EMAIL,
	];
	private CommunicationService $communicationService;
	private Service\Providers\ProfileProvider $profileProvider;

	/**
	 * @param \Bitrix\Sign\Repository\MemberRepository|null $memberRepository
	 * @param \Bitrix\Sign\Repository\DocumentRepository|null $documentRepository
	 */
	public function __construct(
		?MemberRepository $memberRepository = null,
		?DocumentRepository $documentRepository = null,
		?FileRepository $fileRepository = null,
		?Service\Integration\Crm\B2eDocumentService $b2eDocumentService = null,
	)
	{
		$container = Container::instance();
		$this->memberRepository = $memberRepository ?? $container->getMemberRepository();
		$this->documentRepository = $documentRepository ?? $container->getDocumentRepository();
		$this->fileRepository = $fileRepository ?? $container->getFileRepository();
		$this->communicationService = new CommunicationService(Main\Engine\CurrentUser::get()->getId());
		$this->b2eDocumentService = $b2eDocumentService ?? $container->getB2eDocumentService();
		$this->profileProvider = $container->getServiceProfileProvider();
	}

	/**
	 * @param string $documentUid
	 * @param string $entityType
	 * @param int $entityId
	 * @param int $party
	 * @param int $presetId
	 *
	 * @return \Bitrix\Main\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function addForDocument(
		string $documentUid,
		string $entityType,
		int $entityId,
		int $party,
		int $presetId = 0,
		?int $representativeId = null,
		?string $role = null,
	): Main\Result
	{
		$document = $this->documentRepository->getByUid($documentUid);

		if (!$document)
		{
			return (new Main\Result())->addError(
				new Main\Error(Loc::getMessage('SIGN_SERVICE_MEMBER_DOCUMENT_NOT_FOUND'))
			);
		}

		if (!in_array($entityType, self::ALLOWED_ENTITY_TYPES, true))
		{
			return (new Main\Result())->addError(
				new Main\Error(
					Loc::getMessage('SIGN_SERVICE_MEMBER_ADD_ERROR'),
					'MEMBER_ENTITY_TYPE_NOT_ALLOWED'
				),
			);
		}

		$member = $this->memberRepository->getByDocumentAndPartAndEntityTypeAndEntityId(
			$document->id,
			$party,
			$entityType,
			$entityId,
		);
		if ($member?->uid !== null)
		{
			return (new Main\Result())->setData(['member' => $member]);
		}

		$addResult = $this->memberRepository->add(new Item\Member(
			documentId: $document->id,
			party: $party,
			entityType: $entityType,
			entityId: $entityId,
			presetId: $presetId,
			role: $role,
		));

		if (!$addResult->isSuccess())
		{
			return (new Main\Result())->addError(
				new Main\Error(
					Loc::getMessage('SIGN_SERVICE_MEMBER_ADD_ERROR'),
					'MEMBER_ADD_ERROR'
				)
			);
		}

		/** @var Item\Member $member */
		$member = $addResult->getData()['member'];
		$this->setDefaultCommunications($member);

		if (
			Type\DocumentScenario::isB2EScenario($document->scenario)
			&& $representativeId
			&& $member->role === Type\Member\Role::ASSIGNEE
		)
		{
			$this->setRepresentativeDefaultCommunications($member, $representativeId);
		}

		//@todo
		if ($presetId === 0 && in_array($member->entityType, [EntityType::COMPANY, EntityType::CONTACT], true))
		{
			$member->presetId = $this->prepareDefaultCrmRequisite($member)->getData()['PRESET_ID'];
		}
		$this->memberRepository->update($member);

		return $addResult;
	}

	private function setRepresentativeDefaultCommunications(Item\Member $member, int $representativeId): void
	{
		$fakeMember = new Item\Member(
			entityType: Type\Member\EntityType::USER,
			entityId: $representativeId,
		);
		$this->setDefaultCommunications($fakeMember);
		// should return when channels will be available in b2e document
		$member->channelType = Type\Member\ChannelType::IDLE; // $fakeMember->channelType;
		$member->channelValue = $fakeMember->channelValue;
	}

	public function setupB2eMembers(string $documentUid, Item\MemberCollection $memberCollection, int $representativeId): Main\Result
	{
		$document = $this->documentRepository->getByUid($documentUid);
		if (!$document)
		{
			return (new Main\Result())->addError(
				new Main\Error(Loc::getMessage('SIGN_SERVICE_MEMBER_DOCUMENT_NOT_FOUND')),
			);
		}

		if (!Type\DocumentScenario::isB2EScenario($document->scenario))
		{
			return (new Main\Result())->addError(
				new Main\Error('Wrong document scenario'),
			);
		}

		$result = new Main\Result();
		foreach ($memberCollection as $member)
		{
			if ($member->entityId <= 0)
			{
				return $result->addError(new Main\Error('Invalid `entityId` field value'));
			}
		}

		$maxParty = 1;
		foreach ($memberCollection as $member)
		{
			if ($member->party > $maxParty)
			{
				$maxParty = $member->party;
			}
		}

		// First and second party validation
		$noneSignerMembers = $memberCollection->filter(fn(Item\Member $member) => $member->role !== Type\Member\Role::SIGNER);
		$signers = $memberCollection->filter(fn(Item\Member $member) => $member->role === Type\Member\Role::SIGNER);
		$assignee = $noneSignerMembers->findFirstByRole(Type\Member\Role::ASSIGNEE);

		foreach (range(1, $maxParty - 1) as $party)
		{
			$membersInPartyAmount = $memberCollection->filterByParty($party)->count();
			if ($membersInPartyAmount > 1)
			{
				return $result->addError(new Main\Error("Only last party can has multiple members"));
			}
		}
		if ($assignee === null)
		{
			return $result->addError(new Main\Error("Assignee member is required"));
		}
		if ($assignee->entityType !== EntityType::COMPANY)
		{
			return $result->addError(new Main\Error('`entityType` of assignee must be `company`'));
		}

		foreach ($signers as $member)
		{
			if ($member->entityType !== EntityType::USER)
			{
				return $result->addError(new Main\Error('All signers `entityType` must be `user`'));
			}
		}

		if (B2eTariff::instance()->isB2eSignersCountRestricted($signers->count()))
		{
			return $result->addError(B2eTariff::instance()->getSignersCountAccessError());
		}

		$savedAssignee = $this->memberRepository
			->listByDocumentIdWithRole($document->id, Type\Member\Role::ASSIGNEE, 1)
			->getFirst()
		;
		if ($savedAssignee !== null)
		{
			$assignee->presetId ??= $savedAssignee->presetId;
		}

		$result = $this->memberRepository->deleteAllByDocumentId($document->id);
		if (!$result->isSuccess())
		{
			return (new Main\Result())->addError(
				new Main\Error("Error trying to delete previous saved members`"),
			);
		}

		$userMembers = new Item\MemberCollection();
		foreach ($memberCollection as $member)
		{
			if ($member->entityType === EntityType::USER)
			{
				$member->documentId = $document->id;
				$member->channelType = Type\Member\ChannelType::IDLE;
				$member->channelValue = 'stub@at.com';
				$userMembers->add($member);
			}
			else
			{
				$result = $this->addForDocument(
					documentUid: $documentUid,
					entityType: $member->entityType,
					entityId: $member->entityId,
					party: $member->party,
					presetId: $member->presetId ?? 0,
					representativeId: $representativeId,
					role: $member->role,
				);

				if (!$result->isSuccess())
				{
					$this->memberRepository->deleteAllByDocumentId($document->id);
					return $result;
				}
			}
		}

		$result = $this->memberRepository->addMany($userMembers);
		if (!$result->isSuccess())
		{
			$this->memberRepository->deleteAllByDocumentId($document->id);

			return $result;
		}

		$companyMember = $assignee;
		if (!$document->isTemplated())
		{
			$result = $this->b2eDocumentService->setMyCompany($document, $companyMember->entityId);
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		if ($document->parties !== $maxParty)
		{
			$document->parties = $maxParty;
			$result = $this->documentRepository->update($document);
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		return new Main\Result();
	}

	public function cleanByDocumentUid(string $documentUid): Main\Result
	{
		$document = $this->documentRepository->getByUid($documentUid);
		if (!$document)
		{
			return (new Main\Result())->addError(
				new Main\Error(Loc::getMessage('SIGN_SERVICE_MEMBER_DOCUMENT_NOT_FOUND'))
			);
		}

		return $this->memberRepository->deleteAllByDocumentId($document->id);
	}

	/**
	 * @param string $documentUid
	 * @param string $entityType
	 * @param string $entityId
	 * @param int $party
	 *
	 * @return \Bitrix\Main\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function removeFromDocumentAndPart(
		string $documentUid,
		string $entityType,
		int $entityId,
		int $party,
	): Main\Result
	{
		$document = $this->documentRepository->getByUid($documentUid);

		if (!$document)
		{
			return (new Main\Result())->addError(
				new Main\Error(Loc::getMessage('SIGN_SERVICE_MEMBER_DOCUMENT_NOT_FOUND'))
			);
		}

		$member = $this->memberRepository->getByDocumentIdWithParty(
			$document->id,
			$party,
			$entityType,
			$entityId
		);

		if (!$member->id)
		{
			return (new Main\Result())->addError(new Main\Error(
				Loc::getMessage('SIGN_SERVICE_MEMBER_NOT_FOUND',
					'MEMBER_NOT_FOUND'
				))
			);
		}

		$this->memberRepository->deleteById($member->id);

		return new Main\Result();
	}

	/**
	 * @param string $uid
	 *
	 * @return \Bitrix\Main\Result
	 */
	public function remove(string $uid): Main\Result
	{
		$member = $this->memberRepository->getByUid($uid);

		if (!$member->id)
		{
			return (new Main\Result())->addError(
				new Main\Error(Loc::getMessage('SIGN_SERVICE_MEMBER_NOT_FOUND'))
			);
		}

		$this->memberRepository->deleteById($member->id);
		return (new Main\Result());
	}

	/**
	 * @param string $uid
	 * @param string $channelType
	 * @param string $channelValue
	 *
	 * @return Main\Result
	 */
	public function modifyCommunicationChannel(string $uid, string $channelType, string $channelValue): Main\Result
	{
		if (!in_array($channelType, self::ALLOWED_CHANNEL_TYPES, true))
		{
			return (new Main\Result())->addError(
				new Main\Error(Loc::getMessage('SIGN_SERVICE_MEMBER_CHANNEL_NOT_ALLOWED'))
			);
		}

		$member = $this->memberRepository->getByUid($uid);

		if (!$member?->id)
		{
			return (new Main\Result())->addError(
				new Main\Error(Loc::getMessage('SIGN_SERVICE_MEMBER_NOT_FOUND'))
			);
		}

		$member->channelValue = $channelValue;
		$member->channelType = $channelType;

		$result = $this->validateMemberChannelLicenceRestrictions($member);
		if (!$result->isSuccess())
		{
			return $result;
		}

		return $this->memberRepository->update($member);
	}

	/**
	 * @param string $uid
	 *
	 * @return \Bitrix\Sign\Item\Member|null
	 */
	public function getByUid(string $uid): ?Item\Member
	{
		return $this->memberRepository->getByUid($uid);
	}

	/**
	 * @param int $memberId
	 *
	 * @return \Bitrix\Sign\Item\Member|null
	 */
	public function getById(int $memberId): ?Item\Member
	{
		return $this->memberRepository->getById($memberId);
	}

	public function listByDocumentId(int $documentId): Item\MemberCollection
	{
		return $this->memberRepository->listByDocumentId($documentId);
	}

	private function prepareDefaultCrmRequisite(Item\Member $member): Main\ORM\Data\AddResult|Main\Result
	{
		$document = $this->documentRepository->getById($member->documentId);
		if ($document === null)
		{
			return (new Main\Result())->addError(new Main\Error('Linked with member document doesnt exist'));
		}

		$presetId = $member->role === Type\Member\Role::ASSIGNEE
			? CRM::getMyDefaultPresetId($document->entityId, $member->entityId)
			: CRM::getOtherSidePresetId($document->entityId)
		;

		if (!$presetId && $member->entityId)
		{
			return CRM::createDefaultRequisite(
				$document->entityId,
				$member->entityId,
				\CCrmOwnerType::ResolveID($member->entityType)
			);
		}
		$addResult = new Main\ORM\Data\AddResult();
		$addResult->setId($presetId);
		$addResult->setData(['PRESET_ID' => $presetId]);

		return $addResult;
	}

	/**
	 * @param \Bitrix\Sign\Item\Member $member
	 *
	 * @return array
	 */
	public function getCommunications(Item\Member $member): array
	{
		$connector = (new \Bitrix\Sign\Connector\MemberConnectorFactory())->create($member);

		$values = $connector->fetchFields();
		$communications = [];
		foreach ($values as $field)
		{
			if ($field->name === 'FM')
			{
				foreach ($field->data as $type => $multipleField)
				{
					$communicationTypes = [self::CHANNEL_TYPE_PHONE, self::CHANNEL_TYPE_EMAIL,];
					if (in_array($type, $communicationTypes))
					{
						foreach ($multipleField as $communication)
						{
							if (!isset($communications[$type]))
							{
								$communications[$type] = [];
							}
							$communications[$type][] = $communication;
						}
					}
				}
			}
		}

		return $communications;
	}

	public function saveStampFile(int $fileId, Item\Member $member): Main\Result
	{
		if ($member->entityType !== EntityType::COMPANY)
		{
			return (new Main\Result())->addError(new Main\Error("Member must be the company. Now: `{$member->entityType}`"));
		}
		if ($member->documentId === null)
		{
			return (new Main\Result())->addError(new Main\Error("Member must be the company. Now: `{$member->entityType}`"));
		}
		$stamp = new File($fileId);

		if (!$stamp->isExist())
		{
			return (new Main\Result())->addError(new Main\Error("File with id: `$fileId` doesnt exist"));
		}
		$stamp->setModule('crm');
		$savedStampFileId = $stamp->save();
		if ($savedStampFileId === null)
		{
			$stamp->unlink();
			return (new Main\Result())->addError(new Main\Error("Cant save stamp file"));
		}

		$result = \Bitrix\Sign\Integration\CRM::saveCompanyStamp($member->entityId, $stamp);
		if (!$result)
		{
			$stamp->unlink();
			return (new Main\Result())->addError(new Main\Error("Cant save stamp file"));
		}
		$member->stampFileId = $savedStampFileId;

		$updateResult = $this->memberRepository->update($member);
		if (!$updateResult->isSuccess())
		{
			return (new Main\Result())->addErrors($updateResult->getErrors());
		}

		return (new Main\Result())->setData(['fileId' => $stamp->getId()]);
	}

	public function getStampFileFromMemberOrEntity(Item\Member $member): ?Item\Fs\File
	{
		$stampFileId = $member->stampFileId;
		if ($stampFileId === null)
		{
			return $this->getMemberStampFromEntity($member);
		}

		return $this->fileRepository->getById($stampFileId);
	}

	public function getLinkForSigning(Item\Member $member): \Bitrix\Main\Result
	{
		if (!in_array($member->role, [
			Type\Member\Role::ASSIGNEE,
			Type\Member\Role::SIGNER,
			Type\Member\Role::EDITOR,
			Type\Member\Role::REVIEWER,
		], true))
		{
			return (new Main\Result())->addError(new Main\Error('Access denied', 'ACCESS_DENIED'));
		}

		$document = $this->documentRepository->getById($member->documentId);

		if (!Type\DocumentScenario::isB2EScenario($document->scenario))
		{
			return (new Main\Result())->addError(new Main\Error('Access denied', 'ACCESS_DENIED'));
		}

		$result = Service\Container::instance()
			->getApiService()
			->post('v1/b2e.member.getlinkforsigning/' . $document->uid . '/' . $member->uid . '/')
		;

		return (new Main\Result())
			->addErrors($result->getErrors())
			->setData([
				'uri' => $result->getData()['uri'] ?? null,
			])
		;
	}

	public function getLinkForSignedFile(Item\Member $member): \Bitrix\Main\Result
	{
		if (!in_array($member->role, [
			Type\Member\Role::SIGNER,
		], true))
		{
			return (new Main\Result())->addError(new Main\Error('Access denied', 'ACCESS_DENIED'));
		}

		if ($member->status !== Type\MemberStatus::DONE)
		{
			return (new Main\Result())->addError(new Main\Error('Access denied', 'ACCESS_DENIED'));
		}

		$document = $this->documentRepository->getById($member->documentId);

		if (!$document)
		{
			return (new Main\Result())->addError(new Main\Error('no such document'));
		}

		$downloadUrl = null;

		if (DocumentScenario::isB2EScenario($document->scenario))
		{
			$operation = new \Bitrix\Sign\Operation\GetSignedB2eFileUrl(
				\Bitrix\Sign\Type\EntityType::MEMBER,
				$member->id,
				\Bitrix\Sign\Type\EntityFileCode::SIGNED,
			);
			$result = $operation->launch();
			if ($result->isSuccess() && $operation->ready)
			{
				$downloadUrl = $result->getData()['url'] ?? '';
			}
		}
		else
		{
			$operation = new \Bitrix\Sign\Operation\GetSignedFilePdfUrl(
				$document->uid,
				$member->uid,
			);
			if ($operation->launch()->isSuccess())
			{
				$downloadUrl = $operation->url;
			}
		}

		return $downloadUrl
			? (new Main\Result())->setData(['url' => $downloadUrl])
			: (new Main\Result())->addError(new Error('not ready for download'))
		;
	}

	public function getUserIdForMember(Item\Member $member, ?Item\Document $document = null): ?int
	{
		if ($member->entityType === EntityType::COMPANY && $document?->id !== $member->documentId)
		{
			$document = $this->documentRepository->getById($member->documentId);
		}

		return match ($member->entityType)
		{
			EntityType::COMPANY => $document->representativeId,
			EntityType::USER => $member->entityId,
			default => null,
		};
	}

	/**
	 * @return list<int>
	 */
	public function getUserIdsForMembers(Item\MemberCollection $memberCollection, ?Item\Document $document = null): array
	{
		$result = [];
		foreach ($memberCollection as $member)
		{
			$userId = $this->getUserIdForMember($member, $document);
			if (is_int($userId))
			{
				$result[] = $userId;
			}
		}

		return $result;
	}

	public function getCurrentParticipantFromCompanySide(Item\Document $document): ?Item\Member
	{
		return $this->memberRepository
			->listByDocumentIdExcludeRole($document->id, Type\Member\Role::SIGNER)
			->filterByStatus(Type\MemberStatus::getReadyForSigning())
			->getFirst()
		;
	}

	public function getAssignee(Item\Document $document): ?Item\Member
	{
		return $this->memberRepository
			->listByDocumentIdWithRole($document->id, Type\Member\Role::ASSIGNEE, 1)
			->getFirst()
		;
	}

	public function getMemberRepresentedName(Item\Member $member): ?string
	{
		$userId = $this->getUserIdForMember($member);

		if ($userId)
		{
			return $this->getUserRepresentedName($userId);
		}

		return MemberDataPicker::createByMember($member)
			->getName()
		;
	}

	public function getUserRepresentedName(int $userId): string
	{
		$name = $this->profileProvider->loadFieldData($userId, 'UF_LEGAL_NAME')->value;
		$lastName = $this->profileProvider->loadFieldData($userId, 'UF_LEGAL_LAST_NAME')->value;
		$secondName = $this->profileProvider->loadFieldData($userId, 'UF_LEGAL_PATRONYMIC_NAME')->value;

		return $this->profileProvider->getFormattedName($name, $lastName, $secondName);
	}

	private function getMemberStampFromEntity(Item\Member $member): ?Item\Fs\File
	{
		if ($member->entityType !== EntityType::COMPANY || $member->entityId === null)
		{
			return null;
		}
		$oldFileEntity = CRM::getCompanyStamp($member->entityId);
		$fileId = $oldFileEntity?->getId();
		if ($fileId === null)
		{
			return null;
		}

		return $this->fileRepository->getById($fileId);
	}

	private function setDefaultCommunications(Item\Member $member): void
	{
		$communications = MemberDataPicker::createByMember($member)
			->getCommunications()
		;

		$isSmsAllowed = \Bitrix\Sign\Restriction::isSmsAllowed();
		if ($isSmsAllowed && isset($communications[self::CHANNEL_TYPE_PHONE]))
		{
			$member->channelType = self::CHANNEL_TYPE_PHONE;
			$member->channelValue = $communications[self::CHANNEL_TYPE_PHONE][0] ?? null;
			return;
		}

		if (isset($communications[self::CHANNEL_TYPE_EMAIL]))
		{
			$member->channelType = self::CHANNEL_TYPE_EMAIL;
			$member->channelValue = $communications[self::CHANNEL_TYPE_EMAIL][0] ?? null;
		}
	}

	private function validateMemberChannelLicenceRestrictions(Item\Member $member): Main\Result
	{
		return $this->communicationService->validateMemberChannelLicenceRestrictions($member);
	}

	public function getMemberOfDocument(Item\Document $document, string $memberUid): ?Item\Member
	{
		if (empty($memberUid))
		{
			return null;
		}

		$member = $this->memberRepository->getByUid($memberUid);

		return $member->documentId === $document->id ? $member : null;
	}

	public function isUserLinksWithMember(Item\Member $member, Item\Document $document, int $userId): bool
	{
		return $this->getUserIdForMember($member, $document) === $userId;
	}

	public function countUnfinishedSigners(int $documentId): int
	{
		return $this->memberRepository
			->countMembersByDocumentIdAndRoleAndStatus($documentId, MemberStatus::getStatusesNotFinished())
		;
	}

	public function countSuccessfulSigners(int $documentId): int
	{
		return $this->memberRepository->countMembersByDocumentIdAndRoleAndStatus($documentId, [MemberStatus::DONE]);
	}

	public function countReadySigners(int $documentId): int
	{
		return $this->memberRepository->countMembersByDocumentIdAndRoleAndStatus(
				$documentId,
				[MemberStatus::READY, MemberStatus::STOPPABLE_READY]
			)
		;
	}

	public function countWaitingSigners(int $documentId): int
	{
		return $this->memberRepository->countMembersByDocumentIdAndRoleAndStatus($documentId, [MemberStatus::WAIT]);
	}

	public function setProfileProviderCache(?Service\Cache\Memory\Sign\UserCache $userCache): static
	{
		$this->profileProvider->setCache($userCache);

		return $this;
	}

	/**
	 * Skip invitation if initiator signs immediately after document creation.
	 *
	 * @todo if part > 1, check userId for reviewer/editor parties
	 *
	 * @param Item\Member $member
	 * @param Item\Document $document
	 *
	 * @return bool
	 */
	public function skipChatInvitationForMember(Item\Member $member, Item\Document $document): bool
	{
		// crm 24.800.0 dependency
		if (
			! Main\Loader::includeModule('crm')
			|| !defined('\Bitrix\Crm\Timeline\SignDocument\Channel::TYPE_B24')
		)
		{
			return false;
		}

		return
			$member->role === Role::ASSIGNEE
			&& $member->party === 1
			&& $this->getUserIdForMember($member) === $document->createdById
		;
	}
}

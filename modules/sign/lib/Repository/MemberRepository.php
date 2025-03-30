<?php

namespace Bitrix\Sign\Repository;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\UpdateResult;
use Bitrix\Main\Error;
use Bitrix\Main;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Filter\Condition;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Security\Random;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Internal;
use Bitrix\Sign\Item;
use Bitrix\Sign\Model\ItemBinder\MemberBinder;
use Bitrix\Sign\Service\Cache\Memory\Sign\UserCache;
use Bitrix\Sign\Type;
use Bitrix\Sign\Type\DocumentStatus;
use Bitrix\Sign\Type\Member\EntityType;
use Bitrix\Sign\Type\Member\Notification\ReminderType;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\MemberStatus;

class MemberRepository
{
	public const SIGN_DOCUMENT_LIST_QUERY_REF_FIELD_NAME_COMPANY = 'REF_COMPANY';

	private ?UserCache $userCache = null;
	/**
	 * @var list<string>
	 */
	private array $userCacheFields = [
		'ID',
		'NAME',
		'SECOND_NAME',
		'LAST_NAME',
		'LOGIN',
	];

	/**
	 * @param \Bitrix\Sign\Item\Member $item
	 *
	 * @return \Bitrix\Main\Result
	 */
	public function add(Item\Member $item): Main\Result
	{
		$item->uid = $this->generateUniqueUid();

		$now = new DateTime();
		$filledMemberEntity = $this
			->extractModelFromItem($item)
			->setDateCreate($now)
			->setDateModify($now)
		;

		$saveResult = $filledMemberEntity->save();

		if (!$saveResult->isSuccess())
		{
			return (new Main\Result())->addErrors($saveResult->getErrors());
		}

		$item->id = $saveResult->getId();
		$item->initOriginal();

		return (new Main\Result())->setData(['member' => $item]);
	}

	public function deleteById(int $id)
	{
		Internal\MemberTable::delete($id);
	}

	public function deleteAllByDocumentId(int $documentId): Main\Result
	{
		try
		{
			Internal\MemberTable::deleteByFilter([
				'=DOCUMENT_ID' => $documentId,
			]);
		}
		catch (Main\ArgumentException $e)
		{
			return (new Main\Result())->addError(new Main\Error($e->getMessage()));
		}

		return new Main\Result();
	}

	/**
	 * @param \Bitrix\Sign\Internal\Member|null $model
	 *
	 * @return \Bitrix\Sign\Item\Member
	 */
	private function extractItemFromModel(?Internal\Member $model, ?Main\EO_User $user = null): Item\Member
	{
		if (!$model)
		{
			return new Item\Member();
		}

		$name = match ($model->getEntityType())
		{
			EntityType::CONTACT => self::getCrmContactName($model->getEntityId()),
			EntityType::USER => self::getNameForUser($model->getEntityId(), $user),
			default => null,
		};

		// b2e assignee or b2b first party
		$companyName = $model->getEntityType() === EntityType::COMPANY
			? self::getCrmCompanyName($model->getEntityId())
			: null
		;

		$roleNumber = $model->getRole();
		$party = $model->getPart();
		if ($roleNumber === null)
		{
			$role = \Bitrix\Sign\Compatibility\Role::createByParty($party);
		}
		else
		{
			$role = $this->convertIntToRole($roleNumber);
		}

		return new Item\Member(
			documentId: $model->getDocumentId(),
			party: $party,
			id: $model->getId(),
			uid: $model->getUid(),
			status: $model->getSigned(),
			name: $name,
			companyName: $companyName,
			channelType: $model->getCommunicationType(),
			channelValue: $model->getCommunicationValue(),
			dateSigned: $model->getDateSign(),
			dateCreated: $model->getDateCreate(),
			entityType: $model->getEntityType(),
			entityId: $model->getEntityId(),
			presetId: $model->getPresetId(),
			signatureFileId: $model->getSignatureFileId(),
			stampFileId: $model->getStampFileId(),
			role: $role,
			configured: $model->getConfigured(),
			reminder: new Item\Member\Reminder(
				lastSendDate: $model->getReminderLastSendDate(),
				plannedNextSendDate: $model->getReminderPlannedNextSendDate(),
				completed: $model->getReminderCompleted(),
				type: ReminderType::tryFromInt($model->getReminderType()) ?? ReminderType::NONE,
				startDate: $model->getReminderStartDate(),
			),
			dateSend: $model->getDateSend(),
			employeeId: $model->getEmployeeId(),
			hcmLinkJobId: $model->getHcmlinkJobId(),
			dateStatusChanged: $model->getDateStatusChanged(),
		);
	}

	/**
	 * @param \Bitrix\Sign\Item\Member $item
	 *
	 * @return \Bitrix\Sign\Internal\Member
	 */
	private function extractModelFromItem(Item\Member $item): Internal\Member
	{
		return $this->getFilledModelFromItem($item);
	}

	/**
	 * @param \Bitrix\Sign\Internal\MemberCollection $modelCollection
	 *
	 * @return \Bitrix\Sign\Item\MemberCollection
	 */
	private function extractItemCollectionFromModelCollection(Internal\MemberCollection $modelCollection): Item\MemberCollection
	{
		$models = $modelCollection->getAll();

		$users = $this->getUserModels($modelCollection);
		$this->userCache?->setCache($users);
		$this->userCache?->setCachedFields($this->userCacheFields);

		$items = array_map(
			fn(Internal\Member $member) => $this->extractItemFromModel($member,$users[$member->getEntityId()] ?? null),
			$models,
		);

		return new Item\MemberCollection(...$items);
	}

	/**
	 * @param \Bitrix\Sign\Item\Member $item
	 *
	 * @return \Bitrix\Sign\Internal\Member
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getFilledModelFromItem(Item\Member $item): Internal\Member
	{
		$model = Internal\MemberTable::createObject(true);

		return $model
			->setCommunicationValue($item->channelValue)
			->setCommunicationType($item->channelType)
			->setPart($item->party)
			->setRole($this->convertRoleToInt($item->role ?? \Bitrix\Sign\Compatibility\Role::createByParty($item->party)))
			->setDocumentId($item->documentId)
			->setPresetId($item->presetId)
			->setEntityId($item->entityId)
			->setEntityType($item->entityType)
			->setHash($item->uid)
			->setStampFileId($item->stampFileId)
			->setSignatureFileId($item->signatureFileId)
			->setModifiedById(Main\Engine\CurrentUser::get()->getId())
			->setCreatedById(Main\Engine\CurrentUser::get()->getId())
			->setContactId($item->entityType === EntityType::CONTACT ? $item->entityId : 0)
			->setMute('N')
			->setSigned($item->status)
			->setVerified('N')
			->setConfigured($item->configured)
			->setReminderStartDate($item->reminder->startDate)
			->setReminderType($item->reminder->type->toInt())
			->setReminderLastSendDate($item->reminder->lastSendDate)
			->setReminderPlannedNextSendDate($item->reminder->plannedNextSendDate)
			->setReminderCompleted($item->reminder->completed)
			->setEmployeeId($item->employeeId)
			->setHcmlinkJobId($item->hcmLinkJobId)
			->setDateStatusChanged($item->dateStatusChanged)
		;
	}

	public function listByDocumentIdWithParty(int $documentId, int $party, int $limit = 0): Item\MemberCollection
	{
		$models = Internal\MemberTable
			::query()
			->addSelect('*')
			->where('DOCUMENT_ID', $documentId)
			->where('PART', $party)
		;
		if ($limit)
		{
			$models->setLimit($limit);
		}

		return $this->extractItemCollectionFromModelCollection($models->fetchCollection());
	}

	public function listMembersByDocumentIdAndUserIds(int $documentId, int $representativeId, int ...$userIds): Item\MemberCollection
	{
		if (empty($userIds))
		{
			return new Item\MemberCollection();
		}

		$filter = Query::filter()
		   ->logic('or')
		   ->where(
			   Query::filter()
					->logic('and')
					->where('ENTITY_TYPE', '=', EntityType::USER)
					->whereIn('ENTITY_ID', $userIds),
		   )
		;

		if (in_array($representativeId, $userIds, true))
		{
			$filter->where(
				Query::filter()
					 ->logic('and')
					 ->where('ENTITY_TYPE', '=', EntityType::COMPANY)
					 ->where('ROLE', '=', $this->convertRoleToInt(Role::ASSIGNEE))
					 ->where('DOCUMENT.REPRESENTATIVE_ID', '=', $representativeId),
			);
		}

		$models = Internal\MemberTable::query()
			->setSelect(['*'])
			->where('DOCUMENT_ID', $documentId)
			->where($filter)
		;

		return $this->extractItemCollectionFromModelCollection($models->fetchCollection());
	}

	private function prepareListB2eDocumentsByUserIdQuery(
		array $documentStatuses,
		array $memberStatuses,
		int $limit = 30,
	): Query
	{
		return Internal\MemberTable
			::query()
			->setSelect(['ID', 'DATE_SIGN', 'ROLE', 'DOCUMENT.TITLE', 'DOCUMENT.EXTERNAL_ID'])
			->where('DOCUMENT.ENTITY_TYPE', Type\Document\EntityType::SMART_B2E)
			->whereIn('SIGNED', $memberStatuses)
			->whereIn('DOCUMENT.STATUS', $documentStatuses)
			->setLimit($limit)
			->setOrder(['ID' => 'DESC'])
		;
	}

	private function prepareListB2eDocumentsByUserIdCollection(
		Query $query,
	): Item\Integration\SignMobile\MemberDocumentCollection {
		$memberDocuments = array_map(
			fn(Internal\Member $member) => new Item\Integration\SignMobile\MemberDocument(
				memberId: $member->getId(),
				memberRole: $this->convertIntToRole($member->getRole()),
				dateSigned: $member->getDateSign(),
				documentId: $member->getDocument()?->getId(),
				documentTitle: $member->getDocument()?->getTitle(),
				documentExternalId: $member->getDocument()?->getExternalId(),
			),
			$query->fetchCollection()->getAll(),
		);

		return new Item\Integration\SignMobile\MemberDocumentCollection(...$memberDocuments);
	}

	public function listB2eReviewDocumentsByUserId(int $userId, int $limit = 30): Item\Integration\SignMobile\MemberDocumentCollection
	{
		$query = $this->prepareListB2eDocumentsByUserIdQuery(
			[DocumentStatus::SIGNING],
			[MemberStatus::READY],
			$limit,
		);

		$query->where('ROLE', $this->convertRoleToInt(Role::REVIEWER))
			->where('ENTITY_ID', $userId)
			->where('ENTITY_TYPE', '=', EntityType::USER)
		;

		return $this->prepareListB2eDocumentsByUserIdCollection($query);
	}

	public function listB2eSigningDocumentsByUserId(int $userId, int $limit = 30): Item\Integration\SignMobile\MemberDocumentCollection
	{
		$query = $this->prepareListB2eDocumentsByUserIdQuery(
			[DocumentStatus::SIGNING],
			[MemberStatus::READY],
			$limit,
		);

		$filter = Query::filter()
			->logic('or')
			->where(
				Query::filter()
					->logic('and')
					->where('ENTITY_TYPE', '=', EntityType::USER)
					->where('ROLE', $this->convertRoleToInt(Role::SIGNER))
					->where('ENTITY_ID', $userId),
			)
			->where(
				Query::filter()
					->logic('and')
					->where('ENTITY_TYPE', '=', EntityType::COMPANY)
					->where('ROLE', '=', $this->convertRoleToInt(Role::ASSIGNEE))
					->where('DOCUMENT.REPRESENTATIVE_ID', '=', $userId),
			)
		;
		$query->where($filter);

		return $this->prepareListB2eDocumentsByUserIdCollection($query);
	}

	public function listB2eSignedDocumentsByUserId(int $userId, int $limit = 30): Item\Integration\SignMobile\MemberDocumentCollection
	{
		$query = $this->prepareListB2eDocumentsByUserIdQuery(
			[DocumentStatus::DONE],
			[MemberStatus::DONE],
			$limit,
		);

		$query->where('ROLE', $this->convertRoleToInt(Role::SIGNER))
			->where('ENTITY_ID', $userId)
			->where('ENTITY_TYPE', '=', EntityType::USER)
		;

		return $this->prepareListB2eDocumentsByUserIdCollection($query);
	}

	public function listByDocumentIdWithRole(int $documentId, string $role, int $limit = 0, int $offset = 0): Item\MemberCollection
	{
		$models = Internal\MemberTable
			::query()
			->addSelect('*')
			->where('DOCUMENT_ID', $documentId)
			->where('ROLE', $this->convertRoleToInt($role))
		;

		if ($limit)
		{
			$models->setLimit($limit);
		}

		if ($offset)
		{
			$models->setOffset($offset);
		}

		return $this->extractItemCollectionFromModelCollection($models->fetchCollection());
	}

	public function getByDocumentIdWithRole(int $documentId, string $role): ?Item\Member
	{
		return $this->listByDocumentIdWithRole($documentId, $role, 1)->getFirst();
	}

	public function isDocumentHasReviewer(int $documentId): bool
	{
		return $this->isDocumentHasMemberWithRoles($documentId, [Role::REVIEWER]);
	}

	public function isDocumentHasEditor(int $documentId): bool
	{
		return $this->isDocumentHasMemberWithRoles($documentId, [Role::EDITOR]);
	}

	public function isDocumentHasMemberWithRoles(int $documentId, array $roles): bool
	{
		$roleIds = array_map(fn(string $role) => $this->convertRoleToInt($role), $roles);
		$model = Internal\MemberTable
			::query()
			->addSelect('*')
			->where('DOCUMENT_ID', $documentId)
			->whereIn('ROLE', $roleIds)
			->setLimit(1)
		;

		return !!$model->fetch();
	}

	public function listByDocumentIdListAndRoles(array $documentIds, array $roles): Item\MemberCollection
	{
		$roleIds = array_map(fn(string $role) => $this->convertRoleToInt($role), $roles);
		$models = Internal\MemberTable
			::query()
			->addSelect('*')
			->whereIn('DOCUMENT_ID', $documentIds)
			->whereIn('ROLE', $roleIds)
		;

		return $this->extractItemCollectionFromModelCollection($models->fetchCollection());
	}

	public function getByDocumentIdWithParty(int $documentId, int $party, string $entityType, int $entityId): Item\Member
	{
		$models = Internal\MemberTable
			::query()
			->addSelect('*')
			->where('DOCUMENT_ID', $documentId)
			->where('ENTITY_TYPE', $entityType)
			->where('ENTITY_ID', $entityId)
			->where('PART', $party)
			->setLimit(1)
		;

		return $this->extractItemFromModel($models->fetchObject());
	}

	public function countByDocumentIdAndParty(int $documentId, int $party): int
	{
		return Internal\MemberTable::getCount(
			[
				'=DOCUMENT_ID' => $documentId,
				'=PART' => $party,
			],
		);
	}

	public function listByDocumentIdExcludeParty(int $documentId, int $party): Item\MemberCollection
	{
		$models = Internal\MemberTable
			::query()
			->addSelect('*')
			->where('DOCUMENT_ID', $documentId)
			->whereNot('PART', $party)
			->fetchCollection()
		;

		return $this->extractItemCollectionFromModelCollection($models);
	}

	public function listByDocumentIdExcludeRole(int $documentId, string $role): Item\MemberCollection
	{
		$models = Internal\MemberTable
			::query()
			->addSelect('*')
			->where('DOCUMENT_ID', $documentId)
			->whereNot('ROLE', $this->convertRoleToInt($role))
			->fetchCollection()
		;

		return $this->extractItemCollectionFromModelCollection($models);
	}

	/**
	 * @param int $documentId
	 * @param Role::* $roles
	 *
	 * @return \Bitrix\Sign\Item\MemberCollection
	 */
	public function listByDocumentIdExcludeRoles(int $documentId, string... $roles): Item\MemberCollection
	{
		$roleIds = array_map($this->convertRoleToInt(...), $roles);
		$models = Internal\MemberTable::query()
			->addSelect('*')
			->where('DOCUMENT_ID', $documentId)
			->whereNotIn('ROLE', $roleIds)
			->fetchCollection()
		;

		return $this->extractItemCollectionFromModelCollection($models);
	}

	public function listByDocumentId(int $documentId): Item\MemberCollection
	{
		$models = Internal\MemberTable
			::query()
			->addSelect('*')
			->where('DOCUMENT_ID', $documentId)
			->fetchCollection()
		;

		return $this->extractItemCollectionFromModelCollection($models);
	}

	public function getHavingOldestReminderByDocumentId(int $documentId): ?Item\Member
	{
		$memberEntity = Internal\MemberTable
			::query()
			->addSelect('*')
			->where('DOCUMENT_ID', $documentId)
			->setOrder(['REMINDER_START_DATE' => 'DESC'])
			->setLimit(1)
			->fetchObject()
		;

		return $memberEntity
			? $this->extractItemFromModel($memberEntity)
			: null
		;
	}

	public function getByUid(string $uid): ?Item\Member
	{
		$memberEntity = Internal\MemberTable::query()
			->addSelect('*')
			->where('UID', $uid)
			->setCacheTtl(3600)
			->fetchObject();

		return $memberEntity
			? $this->extractItemFromModel($memberEntity)
			: null
		;
	}

	private function generateUniqueUid(): string
	{
		do
		{
			$uid = $this->generateUid();
		}
		while ($this->isMemberWithUidExist($uid));

		return $uid;
	}

	/**
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	private function isMemberWithUidExist(string $uid): bool
	{
		$fetchData = Internal\MemberTable
			::query()
			->addSelect('ID')
			->where('UID', $uid)
			->setLimit(1)
			->fetch()
		;

		return $fetchData !== false;
	}

	private function generateUid(): string
	{
		return Random::getStringByAlphabet(32, Random::ALPHABET_ALPHALOWER | Random::ALPHABET_NUM);
	}

	public function update(Item\Member $item): UpdateResult
	{
		if (!$item->id)
		{
			return (new UpdateResult())->addError(new Error('Document not found'));
		}

		$member = Internal\MemberTable::getById($item->id)
			->fetchObject();

		$binder = new MemberBinder($item, $member);
		$binder->setChangedItemPropertiesToModel();

		$member->setDateModify(new DateTime());

		$result = $member->save();
		if ($result->isSuccess())
		{
			$item->initOriginal();
		}

		return $result;
	}

	public function getByDocumentAndPartAndEntityTypeAndEntityId(
		int $documentId,
		int $party,
		string $entityType,
		int|string $entityId,
	): ?Item\Member
	{
		$model = Internal\MemberTable
			::query()
			->addSelect('*')
			->where('DOCUMENT_ID', $documentId)
			->where('ENTITY_TYPE', $entityType)
			->where('ENTITY_ID', $entityId)
			->where('PART', $party)
			->setLimit(1)
			->exec()
			->fetchObject()
		;

		return $this->extractItemFromModel($model);
	}

	public function listWithFilter(ConditionTree $filter, int $limit = 0): Item\MemberCollection
	{
		$query = Internal\MemberTable::query()
			->addSelect('*')
			->where($filter)
		;
		if ($limit > 0)
		{
			$query->setLimit($limit);
		}

		/** @var Internal\MemberCollection $models */
		$models = $query->fetchCollection();

		return $models === null
			? new Item\MemberCollection()
			: $this->extractItemCollectionFromModelCollection($models)
		;
	}

	/**
	 * @param int $documentId
	 * @param array<MemberStatus::*> $memberStatuses
	 * @param ConditionTree $filter
	 * @param int $limit
	 *
	 */
	public function listByDocumentIdAndMemberStatusesAndCustomFilter(
		int $documentId,
		array $memberStatuses,
		ConditionTree $filter,
		int $limit = 0,
	): Item\MemberCollection
	{
		if ($documentId <= 0)
		{
			return new Item\MemberCollection();
		}

		$query = Internal\MemberTable::query()
			->addSelect('*')
			->where('DOCUMENT_ID', $documentId)
			->whereIn('SIGNED', $memberStatuses)
			->where($filter)
		;
		if ($limit > 0)
		{
			$query->setLimit($limit);
		}

		$models = $query->fetchCollection();

		return $this->extractItemCollectionFromModelCollection($models);
	}

	public function listSignersByUserIdIsDone(
		int $entityId,
		ConditionTree $filter,
		int $limit = 20,
		int $offset = 0,
	): Item\MemberCollection
	{
		$query = Internal\MemberTable
			::query()
			->setSelect(['*'])
			->where('ENTITY_TYPE', EntityType::USER)
			->where('ENTITY_ID', $entityId)
			->whereIn('SIGNED', MemberStatus::DONE)
			->whereIn('DOCUMENT.STATUS', DocumentStatus::getEnding())
			->where('ROLE', $this->convertRoleToInt(Role::SIGNER))
			->where($filter)
			->setLimit($limit)
			->setOffset($offset)
			->setOrder(['ID' => 'desc'])
		;

		$this->updateQueryByRefFields($filter, $query);

		return $this->extractItemCollectionFromModelCollection($query->fetchCollection())
			->setQueryTotal((int)$query->queryCountTotal());
	}

	public function listSignersByUserIdIsNotWait(
		int $entityId,
		ConditionTree $filter,
		int $limit = 20,
		int $offset = 0,
	): Item\MemberCollection
	{
		$query = Internal\MemberTable
			::query()
			->setSelect(['*'])
			->where('ENTITY_TYPE', EntityType::USER)
			->where('ENTITY_ID', $entityId)
			->whereNot('SIGNED', MemberStatus::WAIT)
			->where($filter)
			->setLimit($limit)
			->setOffset($offset)
			->setOrder(['ID' => 'desc'])
		;

		$this->updateQueryByRefFields($filter, $query);

		return $this->extractItemCollectionFromModelCollection($query->fetchCollection())
			->setQueryTotal((int)$query->queryCountTotal());
	}

	public function getAssigneeByDocumentId(int $documentId): ?Item\Member
	{
		$model = Internal\MemberTable
			::query()
			->setSelect(['*'])
			->where('DOCUMENT_ID', $documentId)
			->where('ROLE', $this->convertRoleToInt(Role::ASSIGNEE))
			->setLimit(1)
			->fetchObject()
		;

		return $model === null ? null : $this->extractItemFromModel($model);
	}

	public function getByPartyAndDocumentId(int $documentId, int $part): ?Item\Member
	{
		$model = Internal\MemberTable
			::query()
			->setSelect(['*'])
			->where('DOCUMENT_ID', $documentId)
			->where('PART', $part)
			->setLimit(1)
			->fetchObject()
		;

		if ($model !== null)
		{
			return $this->extractItemFromModel($model);
		}

		return null;
	}

	/**
	 * @param array<Role::*, int> $roleRelevance
	 * @param array<MemberStatus::*, int> $statusRelevance
	 */
	public function listB2eMemberByDocumentId(
		int $documentId,
		?ConditionTree $filter,
		array $roleRelevance = [],
		array $statusRelevance = [],
		int $limit = 10,
		int $offset = 0,
	): Item\MemberCollection
	{
		$roleRelevance = array_flip(
			array_map(
				static fn(string $value): int => Role::convertRoleToInt($value),
				array_flip($roleRelevance),
			),
		);

		$query = Internal\MemberTable
			::query()
			->setSelect(['*'])
			->where('DOCUMENT_ID', $documentId)
			->setLimit($limit)
			->setOffset($offset)
		;
		if (!empty($roleRelevance))
		{
			$query
				->addSelect(new Main\Entity\ExpressionField(
					'ROLE_RELEVANCE',
					$this->getExpressionByRelevance($roleRelevance),
					'ROLE',
					),
				)
				->addOrder('ROLE_RELEVANCE')
			;
		}
		if (!empty($statusRelevance))
		{
			$query
				->addSelect(
				new Main\Entity\ExpressionField(
					'SIGNED_RELEVANCE',
					$this->getExpressionByRelevance($statusRelevance),
					'SIGNED',
					),
				)
				->addOrder('SIGNED_RELEVANCE')
			;
		}
		$query->addOrder('ID');

		if ($filter !== null)
		{
			$query->where($filter);
			$this->updateQueryByRefFields($filter, $query);
		}

		return $this->extractItemCollectionFromModelCollection($query->fetchCollection());
	}

	/**
	 * @return array{REFUSED_MEMBERS_COUNTER: int, SUCCESS_MEMBERS_COUNTER: int, READY_MEMBERS_COUNTER: int}
	 */
	public function getMembersCountersByDocument(Item\Document $document, ?ConditionTree $filter): array
	{
		$signer = Role::convertRoleToInt(Role::SIGNER);
		$refusedMemberStatus = MemberStatus::REFUSED;
		$doneMemberStatus = MemberStatus::DONE;
		$readyMemberStatus = MemberStatus::READY;
		$processingMemberStatus = MemberStatus::PROCESSING;
		$stoppableReadyMemberStatus = MemberStatus::STOPPABLE_READY;

		$query = Internal\MemberTable
			::query()
			->setSelect([
				new Main\Entity\ExpressionField(
					'REFUSED_MEMBERS_COUNTER',
					"SUM(CASE 
					WHEN %s = '$signer' 
					AND %s = '$refusedMemberStatus' 
					THEN 1 ELSE 0 END)",
					['ROLE', 'SIGNED'],
				),
				new Main\Entity\ExpressionField(
					'SUCCESS_MEMBERS_COUNTER',
					"SUM(CASE 
					WHEN %s = '$signer' 
					AND %s = '$doneMemberStatus' 
					THEN 1 ELSE 0 END)",
					['ROLE', 'SIGNED'],
				),
			])
			->where('DOCUMENT_ID', $document->id)
		;

		if ($document->status === DocumentStatus::STOPPED)
		{
			$query->addSelect(
				new Main\Entity\ExpressionField(
					'READY_MEMBERS_COUNTER',
					"SUM(CASE 
					WHEN %s = '$signer' 
					AND (%s = '$readyMemberStatus' 
					OR %s = '$processingMemberStatus') 
					THEN 1 ELSE 0 END)",
					['ROLE', 'SIGNED', 'SIGNED'],
				),
			);
		}
		else
		{
			$query->addSelect(
				new Main\Entity\ExpressionField(
					'READY_MEMBERS_COUNTER',
					"SUM(CASE 
					WHEN %s = '$signer' 
					AND (%s = '$readyMemberStatus' 
					OR %s = '$stoppableReadyMemberStatus' 
					OR %s = '$processingMemberStatus') 
					THEN 1 ELSE 0 END)",
					['ROLE', 'SIGNED', 'SIGNED', 'SIGNED'],
				),
			);
		}

		if ($filter?->hasConditions())
		{
			$query->where($filter);
			$this->updateQueryByRefFields($filter, $query);
		}

		if ($membersCounters = $query->fetch())
		{
			return array_map(static fn (string|null $value): int => (int)$value, $membersCounters);
		}
		else
		{
			return [
				'REFUSED_MEMBERS_COUNTER' => 0,
				'SUCCESS_MEMBERS_COUNTER' => 0,
				'READY_MEMBERS_COUNTER' => 0,
			];
		}
	}

	public function listB2eMembersWithResultFilesForMySafe(
		ConditionTree $filter,
		int $limit = 20,
		int $offset = 0,
	): Item\MemberCollection
	{
		$query = Internal\MemberTable
			::query()
			->setSelect(['*'])
			// load members with result file only
			->registerRuntimeField("",
				(new Main\ORM\Fields\Relations\Reference(
					"RESULT_FILE",
					Internal\FileTable::getEntity(),
					Main\ORM\Query\Join::on("this.ID", 'ref.ENTITY_ID')
						->where('ref.ENTITY_TYPE_ID', \Bitrix\Sign\Type\EntityType::MEMBER)
						->where('ref.CODE', Type\EntityFileCode::SIGNED)
					,
					[
						'join_type' => Main\ORM\Query\Join::TYPE_INNER,
					],
				)),
			)
			->whereIn('DOCUMENT.STATUS', DocumentStatus::getEnding())
			->where($filter)
			// exclude signer result file if the signer has initiated the process
			->whereNot(Query::filter()
				->where('DOCUMENT.INITIATED_BY_TYPE', Type\Document\InitiatedByType::EMPLOYEE->toInt())
				->where('ROLE', Role::convertRoleToInt(Role::SIGNER))
			)
			->setLimit($limit)
			->setOffset($offset)
			->setOrder(['ID' => 'desc'])
		;

		$this->updateQueryByRefFields($filter, $query);

		return $this->extractItemCollectionFromModelCollection($query->fetchCollection())
			->setQueryTotal((int)$query->queryCountTotal())
		;
	}

	public function listByUids(array $uids): Item\MemberCollection
	{
		$models = Internal\MemberTable::query()
			->addSelect('*')
			->whereIn('UID', $uids)
			->fetchCollection()
		;

		return $this->extractItemCollectionFromModelCollection($models);
	}

	/**
	 * @param array<int> $ids
	 * @return Item\MemberCollection
	 */
	public function listByIds(array $ids): Item\MemberCollection
	{
		$models = Internal\MemberTable::query()
			->addSelect('*')
			->whereIn('ID', $ids)
			->fetchCollection()
		;

		return $this->extractItemCollectionFromModelCollection($models);
	}

	public function getById(int $id): ?Item\Member
	{
		$memberEntity = Internal\MemberTable::query()
			->addSelect('*')
			->where('ID', $id)
			->fetchObject();

		return $memberEntity
			? $this->extractItemFromModel($memberEntity)
			: null
		;
	}

	private static function getCrmContactName(int $entityId): ?string
	{
		return \Bitrix\Sign\Integration\CRM\Entity::getContactName($entityId);
	}

	private static function getCrmCompanyName(int $entityId): ?string
	{
		if (!Main\Loader::includeModule('crm'))
		{
			return null;
		}

		return \Bitrix\Crm\Service\Container::getInstance()->getCompanyBroker()->getTitle($entityId);
	}

	private static function getNameForUser(int $userId, ?Main\EO_User $userModel = null): ?string
	{
		if (empty($userId))
		{
			return null;
		}

		$userModel ??= Main\UserTable::getById($userId)->fetchObject();

		return $userModel
			? \CUser::FormatName(
				\Bitrix\Main\Context::getCurrent()->getCulture()->getNameFormat(),
				[
					'LOGIN' => '',
					'NAME' => $userModel->getName(),
					'LAST_NAME' => $userModel->getLastName(),
					'SECOND_NAME' => $userModel->getSecondName(),
				],
				false, false,
			)
			: null
		;
	}

	public function convertRoleToInt(string $role): int
	{
		return Role::convertRoleToInt($role);
	}

	private function convertIntToRole(int $roleNumber): string
	{
		return Role::convertIntToRole($roleNumber);
	}

	public function updateQueryByRefFields(ConditionTree $filter, Query $query): void
	{
		$conditions = array_filter(
			$filter->getConditions(),
			static fn($condition) => ($condition instanceof Condition),
		);
		if (
			!empty($conditions)
			&& !empty(
				array_filter(
					$conditions,
					static fn(Condition $condition) => str_starts_with($condition->getColumn(), self::SIGN_DOCUMENT_LIST_QUERY_REF_FIELD_NAME_COMPANY),
				)
			)
		)
		{
			$query->registerRuntimeField(
				self::SIGN_DOCUMENT_LIST_QUERY_REF_FIELD_NAME_COMPANY,
				new ReferenceField(
					self::SIGN_DOCUMENT_LIST_QUERY_REF_FIELD_NAME_COMPANY,
					Internal\MemberTable::getEntity(),
					Join::on('ref.DOCUMENT_ID', 'this.DOCUMENT_ID'),
					['join_type' => Join::TYPE_INNER],
				),
			);
		}
	}

	public function listB2eMembersWithReadyStatus(int $entityId, int $limit, int $offset): Item\MemberCollection
	{
		$query = $this->getQueryForMemberCollectionReadyStatus($entityId);
		$query->setOffset($offset);
		$query->setLimit($limit);
		$query->setOrder(['ID' => 'DESC']);

		return $this->extractItemCollectionFromModelCollection($query->fetchCollection())
			->setQueryTotal((int)$query->queryCountTotal())
		;
	}

	public function listB2eMembersForMyDocumentsGrid(
		int $entityId,
		int $limit,
		int $offset,
		?Item\MyDocumentsGrid\MyDocumentsFilter $filter = null,
	): Item\MemberCollection
	{
		$query = $this->getQueryForMemberCollectionNotWaitStatus($entityId, $filter);
		$query->setOffset($offset);
		$query->setLimit($limit);

		if ($filter === null || $filter->isNeedActionFeatureSort())
		{
			$readyForSigningWithDelimiters = implode("','", MemberStatus::getReadyForSigning());
			$query
				->addSelect(
					new Main\Entity\ExpressionField(
						name: 'STATUS_RELEVANCE',
						expression: "CASE 
						WHEN (
						 	(
								%s = '" . MemberStatus::READY . "'
								AND %s = {$this->convertRoleToInt(Role::SIGNER)}
								AND %s = '". Type\ProviderCode::GOS_KEY. "'
								AND %s IN ('". DocumentStatus::SIGNING ."', '". DocumentStatus::STOPPED ."')
							)
							OR 
							(
								%s IN ('$readyForSigningWithDelimiters')
								AND %s = '". DocumentStatus::SIGNING ."'
							)
						)
						THEN 0 
						ELSE 1
						END",
						buildFrom: [
								  'SIGNED',
								  'ROLE',
								  'DOCUMENT.PROVIDER_CODE',
								  'DOCUMENT.STATUS',
								  'SIGNED',
								  'DOCUMENT.STATUS',
							  ],
					),
				)
				->addOrder('STATUS_RELEVANCE')
			;
		}

		$query->addOrder('ID', 'DESC');

		return $this->extractItemCollectionFromModelCollection($query->fetchCollection())
			->setQueryTotal((int)$query->queryCountTotal())
			;
	}

	public function isAnyMyDocumentsGridInSignedStatus(int $userId): bool
	{
		$filter = (new Item\MyDocumentsGrid\MyDocumentsFilter(
			statuses: [Type\MyDocumentsGrid\FilterStatus::SIGNED],
		));
		$query = $this->getQueryForMemberCollectionNotWaitStatus($userId, $filter);
		$query->setLimit(1)
			->setSelect(['ID'])
		;

		return !empty($query->fetch());
	}

	public function isAnyMyDocumentsGridInProgressStatus(int $userId): bool
	{
		$filter = (new Item\MyDocumentsGrid\MyDocumentsFilter(
			statuses: [Type\MyDocumentsGrid\FilterStatus::IN_PROGRESS],
		));
		$query = $this->getQueryForMemberCollectionNotWaitStatus($userId, $filter);
		$query->setLimit(1)
			->setSelect(['ID'])
		;

		return !empty($query->fetch());
	}

	public function getSecondSideMembersForMyDocumentsGrid(
		array $documentIds,
		array $memberIds,
	): Item\MemberCollection
	{
		$filter = Query::filter()
			->logic('or')
			->where(
				Query::filter()
					->logic('and')
					->whereIn('DOCUMENT.STATUS', [DocumentStatus::DONE, DocumentStatus::STOPPED])
					->where(
						Query::filter()
							->logic('or')
							->whereNot('SIGNED', MemberStatus::DONE)
							->where(Query::filter()->whereColumn('PART', 'DOCUMENT.PARTIES'))
					)
				,
			)
			->where(
				Query::filter()
					->logic('and')
					->where('SIGNED', MemberStatus::READY)
				,
			);

		$query =  Internal\MemberTable
			::query()
			->addSelect('*')
			->where($filter)
			->whereIn('DOCUMENT_ID', $documentIds)
			->whereNotIn('ID', $memberIds)
			->where('DOCUMENT.ENTITY_TYPE', '=', Type\Document\EntityType::SMART_B2E)
		;
		$query->setOrder(['PART' => 'ASC']);

		return $this->extractItemCollectionFromModelCollection($query->fetchCollection())
			;
	}

	public function getTotalMemberCollectionCountWithNotWaitStatus(
		int $entityId,
		?Item\MyDocumentsGrid\MyDocumentsFilter $filter = null,
	): int
	{
		$query = $this->getQueryForMemberCollectionNotWaitStatus($entityId, $filter);

		return (int)$query->queryCountTotal();
	}

	public function getQueryForMemberCollectionNotWaitStatus(
		int $userId,
		?Item\MyDocumentsGrid\MyDocumentsFilter $filter = null,
	): Query
	{
		$baseFilter = Query::filter()
			->logic('or')
			->where(
				Query::filter()
					->logic('and')
					->where('ENTITY_TYPE', '=', EntityType::USER)
					->where('ENTITY_ID', $userId)
				,
			)
			->where(
				Query::filter()
					->logic('and')
					->where('ENTITY_TYPE', '=', EntityType::COMPANY)
					->where('ROLE', '=', $this->convertRoleToInt(Role::ASSIGNEE))
					->where('DOCUMENT.REPRESENTATIVE_ID', '=', $userId)
				,
			);

		$filterForStopped = Query::filter()
			->logic('and')
			->where('ROLE', $this->convertRoleToInt(Role::SIGNER))
			->where('SIGNED', MemberStatus::STOPPED)
			->where(Query::filter()
				->logic('or')
				->where('DOCUMENT.INITIATED_BY_TYPE', Type\Document\InitiatedByType::COMPANY->toInt())
				->where(
					Query::filter()
						->logic('and')
						->where('DOCUMENT.INITIATED_BY_TYPE', Type\Document\InitiatedByType::EMPLOYEE->toInt())
						->whereColumn('DOCUMENT.STOPPED_BY_ID', 'ENTITY_ID')
				)
			)
		;

		$readyInDoneDocuments = Query::filter()
			->logic('and')
			->whereIn('DOCUMENT.STATUS', DocumentStatus::getFinalStatuses())
			->whereIn('SIGNED', MemberStatus::getReadyForSigning())
			->whereNot(
				Query::filter()
					->logic('and')
					->where('ROLE', $this->convertRoleToInt(Role::SIGNER))
					->where('DOCUMENT.PROVIDER_CODE', Type\ProviderCode::GOS_KEY)
			)
		;

		$excludeStopped = Query::filter()
			->logic('or')
			->where($filterForStopped)
			->where($readyInDoneDocuments);

		$query = Internal\MemberTable
			::query()
			->addSelect('*')
			->whereNot('SIGNED', MemberStatus::WAIT)
			->where('DOCUMENT.ENTITY_TYPE', '=', Type\Document\EntityType::SMART_B2E)
			->where($baseFilter)
			->whereNot($excludeStopped)
			;

		if ($filter?->role === Type\MyDocumentsGrid\ActorRole::INITIATOR)
		{
			$query
				->where('DOCUMENT.INITIATED_BY_TYPE', Type\Document\InitiatedByType::EMPLOYEE->toInt())
				->where('ROLE', $this->convertRoleToInt(Role::SIGNER))
			;
		}
		elseif ($filter?->role === Type\MyDocumentsGrid\ActorRole::REVIEWER)
		{
			$query->where('ROLE', $this->convertRoleToInt(Role::REVIEWER));
		}
		elseif ($filter?->role === Type\MyDocumentsGrid\ActorRole::EDITOR)
		{
			$query->where('ROLE', $this->convertRoleToInt(Role::EDITOR));
		}
		elseif ($filter?->role === Type\MyDocumentsGrid\ActorRole::SIGNER)
		{
			$query->where('ROLE', $this->convertRoleToInt(Role::SIGNER))
				  ->where('DOCUMENT.INITIATED_BY_TYPE', Type\Document\InitiatedByType::COMPANY->toInt())
			;
		}
		elseif ($filter?->role === Type\MyDocumentsGrid\ActorRole::ASSIGNEE)
		{
			$query->where('ROLE', $this->convertRoleToInt(Role::ASSIGNEE));
		}

		if ($filter?->initiators)
		{
			$query->whereIn('DOCUMENT.CREATED_BY_ID', $filter->initiators);
		}

		if ($filter?->editors)
		{
			$this->appendMemberExistConditionByRole(Role::EDITOR, $filter->editors, $query);
		}

		if ($filter?->reviewers)
		{
			$this->appendMemberExistConditionByRole(Role::REVIEWER, $filter->reviewers, $query);
		}

		if ($filter?->signers)
		{
			$this->appendMemberExistConditionByRole(Role::SIGNER, $filter->signers, $query);
		}

		if ($filter?->assignees)
		{
			$query->whereIn('DOCUMENT.REPRESENTATIVE_ID', $filter->assignees);
		}

		if ($filter?->assigneesOrSigners)
		{
			$this->appendAssigneeOrSignerCondition($filter->assigneesOrSigners, $query);
		}

		if ($filter?->companies)
		{
			$subQuery = Internal\MemberTable::query()
				->setCustomBaseTableAlias("sign_internal_member_company")
				->where('DOCUMENT_ID', new \Bitrix\Main\DB\SqlExpression('%s'))
				->where('ENTITY_TYPE', EntityType::COMPANY)
				->whereIn('ENTITY_ID', $filter->companies)
			;
			$query->whereExpr("EXISTS({$subQuery->getQuery()})", ['DOCUMENT_ID']);
		}

		if ($filter?->statuses)
		{
			$statusesFilter = Query::filter()->logic('or');
			foreach ($filter->statuses as $status)
			{
				$this->appendStatusCondition($status, $statusesFilter, $userId);
			}
			$query->where($statusesFilter);
		}

		if ($filter?->dateModifyFrom)
		{
			$query->where('DATE_MODIFY', '>=', $filter->dateModifyFrom);
		}

		if ($filter?->dateModifyTo)
		{
			$query->where('DATE_MODIFY', '<=', $filter->dateModifyTo);
		}

		if ($filter?->text)
		{
			$query->whereLike('DOCUMENT.TITLE', "%$filter->text%");
		}

		return $query;
	}

	private function appendMemberExistConditionByRole(string $role, array $userIds, Query $query): void
	{
		$subQuery = $this->getMemberExistsSubqueryByRole($role, $userIds);
		$query->whereExpr("EXISTS({$subQuery->getQuery()})", ['DOCUMENT_ID']);
	}

	private function getMemberExistsSubqueryByRole(string $role, array $userIds): Query
	{
		return Internal\MemberTable::query()
			->setCustomBaseTableAlias("sign_internal_member_{$role}")
			->where('DOCUMENT_ID', new \Bitrix\Main\DB\SqlExpression('%s'))
			->where('ENTITY_TYPE', EntityType::USER)
			->where('ROLE', $this->convertRoleToInt($role))
			->whereIn('ENTITY_ID', $userIds)
		;
	}

	private function appendAssigneeOrSignerCondition(array $userIds, Query $query): void
	{
		$subQuery = $this->getMemberExistsSubqueryByRole(Role::SIGNER, $userIds);
		$query->where(Query::filter()
		   ->logic('or')
		   ->whereExpr("EXISTS({$subQuery->getQuery()})", ['DOCUMENT_ID'])
		   ->whereIn('DOCUMENT.REPRESENTATIVE_ID', $userIds)
		);
	}

	private function appendStatusCondition(
		Type\MyDocumentsGrid\FilterStatus $status,
		ConditionTree $statusesFilter,
		int $userId,
	): void
	{
		match ($status)
		{
			Type\MyDocumentsGrid\FilterStatus::SIGNED => $this->appendSignedStatusCondition($statusesFilter),
			Type\MyDocumentsGrid\FilterStatus::MY_SIGNED => $this->appendMySignedStatusCondition($statusesFilter),
			Type\MyDocumentsGrid\FilterStatus::IN_PROGRESS => $this->appendInProgressStatusCondition($statusesFilter),
			Type\MyDocumentsGrid\FilterStatus::NEED_ACTION => $this->appendNeedActionStatusCondition($statusesFilter),
			Type\MyDocumentsGrid\FilterStatus::MY_REVIEW => $this->appendMyReviewStatusCondition($statusesFilter),
			Type\MyDocumentsGrid\FilterStatus::MY_EDITED => $this->appendMyEditedStatusCondition($statusesFilter),
			Type\MyDocumentsGrid\FilterStatus::MY_STOPPED => $this->appendMyStoppedStatusCondition($statusesFilter, $userId),
			Type\MyDocumentsGrid\FilterStatus::STOPPED => $this->appendStoppedStatusCondition($statusesFilter),
			Type\MyDocumentsGrid\FilterStatus::MY_ACTION_DONE => $this->appendMyActionDoneStatusCondition($statusesFilter),
		};
	}

	private function appendSignedStatusCondition(ConditionTree $statusesFilter): void
	{
		$statusesFilter->where(
			Query::filter()
				 ->logic('and')
				 ->where('SIGNED', MemberStatus::DONE)
				 ->where('ROLE', $this->convertRoleToInt(Role::SIGNER))
				 ->where(
					 Query::filter()
						  ->logic('or')
						  ->where('PART', '>', '1')
						  ->where('DOCUMENT.STATUS', DocumentStatus::DONE),
				 )
			,
		);
	}

	private function appendMySignedStatusCondition(ConditionTree $statusesFilter): void
	{
		$statusesFilter->where(
			Query::filter()
				->logic('or')
				->where(
					Query::filter()
						->logic('and')
						->whereIn('SIGNED', [MemberStatus::DONE])
						->where('ROLE', $this->convertRoleToInt(Role::ASSIGNEE))
						->whereIn('DOCUMENT.STATUS', [DocumentStatus::SIGNING, DocumentStatus::DONE, DocumentStatus::STOPPED])
				)
				->where(
					Query::filter()
						->logic('and')
						->whereIn('SIGNED', [MemberStatus::STOPPED])
						->where('ROLE', $this->convertRoleToInt(Role::ASSIGNEE))
						->whereIn('DOCUMENT.STATUS', [DocumentStatus::STOPPED, DocumentStatus::DONE])
						// if someone signed in by-company scenario, document not considered stopped
						->where('DOCUMENT.INITIATED_BY_TYPE', Type\Document\InitiatedByType::COMPANY->toInt())
						->whereExpr("EXISTS({$this->getSomeSignedSubQuery()->getQuery()})", ['DOCUMENT_ID']),
				)
		);
	}

	private function appendInProgressStatusCondition(ConditionTree $statusesFilter): void
	{
		$statusesFilter->where(
			Query::filter()
				 ->logic('or')
				 ->where(
					 Query::filter()
						  ->logic('and')
						  ->where('SIGNED', MemberStatus::READY)
						  ->where('ROLE', '=', $this->convertRoleToInt(Role::SIGNER))
						  ->where('DOCUMENT.PROVIDER_CODE', Type\ProviderCode::GOS_KEY)
						  ->whereIn('DOCUMENT.STATUS', [DocumentStatus::SIGNING, DocumentStatus::STOPPED]),
				 )
				 ->where(
					 Query::filter()
						  ->logic('and')
						  ->whereIn('SIGNED', MemberStatus::getReadyForSigning())
						  ->where('DOCUMENT.STATUS', DocumentStatus::SIGNING),
				 )
				 ->where(
					 Query::filter()
						  ->logic('and')
						  ->where('SIGNED', MemberStatus::DONE)
						  ->where('ROLE', $this->convertRoleToInt(Role::SIGNER))
						  ->where('DOCUMENT.STATUS', DocumentStatus::SIGNING)
						  ->where('PART', 1),
				 ),
		);
	}

	private function appendNeedActionStatusCondition(ConditionTree $statusesFilter): void
	{
		$statusesFilter->where(
			Query::filter()
				 ->logic('or')
				 ->where(
					 Query::filter()
						  ->logic('and')
						  ->where('SIGNED', MemberStatus::READY)
						  ->where('ROLE', '=', $this->convertRoleToInt(Role::SIGNER))
						  ->where('DOCUMENT.PROVIDER_CODE', Type\ProviderCode::GOS_KEY)
						  ->whereIn('DOCUMENT.STATUS', DocumentStatus::getEnding()),
				 )
				 ->where(
					 Query::filter()
						  ->logic('and')
						  ->whereIn('SIGNED', MemberStatus::getReadyForSigning())
						  ->where('DOCUMENT.STATUS', DocumentStatus::SIGNING),
				 ),
		);
	}

	private function appendMyReviewStatusCondition(ConditionTree $statusesFilter): void
	{
		$statusesFilter->where(
			Query::filter()
				 ->logic('and')
				 ->where('SIGNED', MemberStatus::DONE)
				 ->where('ROLE', $this->convertRoleToInt(Role::REVIEWER)),
		);
	}

	private function appendMyEditedStatusCondition(ConditionTree $statusesFilter): void
	{
		$statusesFilter->where(
			Query::filter()
				 ->logic('and')
				 ->where('SIGNED', MemberStatus::DONE)
				 ->where('ROLE', $this->convertRoleToInt(Role::EDITOR)),
		);
	}

	private function appendMyStoppedStatusCondition(ConditionTree $statusesFilter, int $userId): void
	{
		$statusesFilter->where(
			Query::filter()
				 ->logic('or')
				 ->where('SIGNED', MemberStatus::REFUSED)
				 ->where(
					 Query::filter()
						  ->logic('and')
						  ->where('DOCUMENT.STATUS', DocumentStatus::STOPPED)
						  ->where('DOCUMENT.STOPPED_BY_ID', $userId)
						  // if someone signed in by-company scenario, document not considered stopped
						  ->where(
							  Query::filter()
								  ->logic('or')
								  ->where('DOCUMENT.INITIATED_BY_TYPE', Type\Document\InitiatedByType::EMPLOYEE->toInt())
								  ->whereExpr("NOT EXISTS({$this->getSomeSignedSubQuery()->getQuery()})", ['DOCUMENT_ID']),
						  )
				 ),
		);
	}

	private function appendStoppedStatusCondition(ConditionTree $statusesFilter): void
	{
		$statusesFilter->where(
			Query::filter()
				 ->logic('and')
				 ->where('ROLE', $this->convertRoleToInt(Role::SIGNER))
				 ->where(Query::filter()
						->logic('or')
						->where('SIGNED', MemberStatus::STOPPED)
					 	->where(Query::filter()
							->logic('and')
							->where('DOCUMENT.STATUS', DocumentStatus::STOPPED)
							->where('DOCUMENT.INITIATED_BY_TYPE', Type\Document\InitiatedByType::EMPLOYEE->toInt())
							->where('SIGNED', MemberStatus::DONE)
					 	)
				 )
			,
		);
	}

	private function appendMyActionDoneStatusCondition(ConditionTree $statusesFilter): void
	{
		$notSignerRoles = [
			$this->convertRoleToInt(Role::ASSIGNEE),
			$this->convertRoleToInt(Role::REVIEWER),
			$this->convertRoleToInt(Role::EDITOR),
		];
		$statusesFilter->where(
			Query::filter()
				 ->logic('or')
				 ->where('SIGNED', MemberStatus::REFUSED)
				 ->where(
					 Query::filter()
						  ->logic('and')
						  ->whereIn('SIGNED', [MemberStatus::DONE, MemberStatus::STOPPED])
						  ->whereIn('ROLE', $notSignerRoles)
				 )
		);
	}

	public function listB2eMembersWithReadyStatusByDocumentIds(array $documentIds, int $entityId): Item\MemberCollection
	{
		$query = $this->getQueryForMemberCollectionReadyStatus($entityId)
			->whereIn('DOCUMENT_ID', $documentIds)
		;

		return $this->extractItemCollectionFromModelCollection($query->fetchCollection())
			->setQueryTotal((int)$query->queryCountTotal())
		;
	}

	public function getCountForCurrentUserAction(int $entityId): int
	{
		return (int)$this->getQueryForMemberCollectionReadyStatus($entityId)->queryCountTotal();
	}

	public function listB2eSigningByDocumentIdAndStatuses(int $documentId, array $statuses, int $limit = 1): Item\MemberCollection
	{
		$result = Internal\MemberTable::query()
			->addSelect('*')
			->where('ENTITY_TYPE', EntityType::USER)
			->whereIn('SIGNED', $statuses)
			->where('DOCUMENT_ID', $documentId)
			->where('ROLE', $this->convertRoleToInt(Role::SIGNER))
			->setLimit($limit)
		;

		return $this->extractItemCollectionFromModelCollection($result->fetchCollection())
			->setQueryTotal((int)$result->queryCountTotal())
		;
	}

	public function listB2eStoppedByDocumentId(int $documentId, int $limit = 3): Item\MemberCollection
	{
		$filter = Query::filter()
			->logic('or')
			->where(
				Query::filter()
					->logic('and')
					->whereIn('SIGNED', [MemberStatus::REFUSED, MemberStatus::STOPPED]),
			)->where(
				Query::filter()
					->logic('and')
					->where('DOCUMENT.STATUS', DocumentStatus::STOPPED)
					->whereNot('SIGNED', MemberStatus::DONE),
			);

		$result = Internal\MemberTable::query()
			->addSelect('*')
			->where('DOCUMENT_ID', $documentId)
			->where('ROLE', $this->convertRoleToInt(Role::SIGNER))
			->where($filter)
			->setLimit($limit)
		;

		return $this->extractItemCollectionFromModelCollection($result->fetchCollection())
			->setQueryTotal((int)$result->queryCountTotal())
		;
	}

	private function getQueryForMemberCollectionReadyStatus(int $entityId): Query
	{
		$filter = Query::filter()
			->logic('or')
			->where(
				Query::filter()
					->logic('and')
					->where('ENTITY_TYPE', '=', EntityType::USER)
					->where('ENTITY_ID', $entityId)
					->whereIn('DOCUMENT.STATUS', [DocumentStatus::SIGNING, DocumentStatus::STOPPED])
					->where(
						Query::filter()
							->logic('or')
							->where(Query::filter()
										 ->logic('and')
										 ->where('SIGNED', MemberStatus::READY)
										 ->where('ROLE', '=', $this->convertRoleToInt(Role::SIGNER))
										 ->where('DOCUMENT.PROVIDER_CODE', Type\ProviderCode::GOS_KEY)
										 ->whereIn('DOCUMENT.STATUS', [DocumentStatus::SIGNING, DocumentStatus::STOPPED]),
							)
							->where(Query::filter()
										 ->logic('and')
										 ->whereIn('SIGNED', MemberStatus::getReadyForSigning())
										 ->where('DOCUMENT.STATUS', DocumentStatus::SIGNING),
							),
					)
				,
			)
			->where(
				Query::filter()
					->logic('and')
					->where('ENTITY_TYPE', '=', EntityType::COMPANY)
					->where('ROLE', '=', $this->convertRoleToInt(Role::ASSIGNEE))
					->where('DOCUMENT.REPRESENTATIVE_ID', '=', $entityId)
					->where('DOCUMENT.STATUS', DocumentStatus::SIGNING)
				,
		);

		return Internal\MemberTable
			::query()
			->addSelect('*')
			->whereIn('SIGNED', MemberStatus::getReadyForSigning())
			->where('DOCUMENT.ENTITY_TYPE', '=', Type\Document\EntityType::SMART_B2E)
			->where($filter)
		;
	}

	/**
	 * @param Internal\MemberCollection $modelCollection
	 *
	 * @return array|array<int, Main\EO_User>
	 *
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getUserModels(Internal\MemberCollection $modelCollection): array
	{
		$userIds = $this->getUserIds($modelCollection);

		if (empty($userIds))
		{
			return [];
		}

		$userModels = Main\UserTable::query()
			->whereIn('ID', $userIds)
			->setSelect($this->userCacheFields)
			->fetchCollection()
		;

		$userModelsById = [];
		foreach ($userModels as $userModel)
		{
			$userModelsById[$userModel->getId()] = $userModel;
		}

		return $userModelsById;
	}

	/**
	 * @param Internal\MemberCollection $modelCollection
	 * @return array|array<int>
	 */
	private function getUserIds(Internal\MemberCollection $modelCollection): array
	{
		$userIds = [];
		foreach ($modelCollection as $member)
		{
			if ($member->getEntityType() !== EntityType::USER)
			{
				continue;
			}

			$entityId = $member->getEntityId();
			if ($entityId)
			{
				$userIds[] = $entityId;
			}
		}

		return $userIds;
	}

	public function setUserCache(?UserCache $cache = null): static
	{
		$this->userCache = $cache;

		return $this;
	}

	public function getUserCache(): ?UserCache
	{
		return $this->userCache;
	}

	public function setAsVerified(Item\Member $item): UpdateResult
	{
		if (!$item->id)
		{
			return (new UpdateResult())->addError(new Error('Document not found'));
		}

		$member = Internal\MemberTable::getById($item->id)->fetchObject();
		if ($member === null)
		{
			return (new UpdateResult())->addError(new Error('Member not found'));
		}

		$member->setVerified('Y');

		return $member->save();
	}

	public function listNotConfiguredByDocumentId(int $documentId, int $limit = 30): Item\MemberCollection
	{
		$models = $this->getNotConfiguredMembersQueryByDocumentId($documentId)
			->addSelect('*')
			->setLimit($limit)
			->fetchCollection()
		;

		return $this->extractItemCollectionFromModelCollection($models);
	}

	public function markAsConfigured(Item\MemberCollection $members): Main\Result
	{
		$result = Internal\MemberTable::updateMulti($members->getIds(), [
			'CONFIGURED' => 1,
		]);

		if ($result->isSuccess())
		{
			foreach ($members as $member)
			{
				$member->configured = 1;
			}
		}

		return $result;
	}

	public function countByDocumentId(int $documentId): int
	{
		return (int)Internal\MemberTable::query()
			->where('DOCUMENT_ID', $documentId)
			->queryCountTotal()
		;
	}

	public function countNotConfiguredByDocumentId(int $documentId): int
	{
		return (int)$this->getNotConfiguredMembersQueryByDocumentId($documentId)
						 ->queryCountTotal()
		;
	}

	/**
	 * @return Internal\EO_Member_Query
	 */
	private function getNotConfiguredMembersQueryByDocumentId(int $documentId): Query
	{
		return Internal\MemberTable::query()
			   ->where('DOCUMENT_ID', $documentId)
			   ->where(Query::filter()
							->logic('or')
							->whereNull('CONFIGURED')
							->where('CONFIGURED', 0),
			   )
		;
	}

	public function addMany(Item\MemberCollection $members): Main\Result
	{
		$ormCollection = new Internal\MemberCollection();
		foreach ($members->toArray() as $item)
		{
			$item->uid = $this->generateUid();

			$now = new DateTime();
			$filledMemberEntity = $this
				->extractModelFromItem($item)
				->setDateCreate($now)
				->setDateModify($now)
			;
			$ormCollection->add($filledMemberEntity);
		}

		return $ormCollection->save(true);
	}

	public function countMembersByDocumentIdAndRoleAndStatus(
		int $documentId,
		array $statuses = [],
		string $role = Role::SIGNER,
	): int
	{
		$query = Internal\MemberTable
			::query()
			->whereIn('DOCUMENT_ID', $documentId)
			->where('ROLE', $this->convertRoleToInt($role))
		;

		if ($statuses)
		{
			$query->whereIn('SIGNED', $statuses);
		}

		return (int)$query->queryCountTotal();
	}

	public function listByDocumentIdAndRoleAndStatus(
		int $documentId,
		string $role,
		int $limit = 0,
		array $statuses = []): Item\MemberCollection
	{
		$query = Internal\MemberTable
			::query()
			->addSelect('*')
			->where('DOCUMENT_ID', $documentId)
			->where('ROLE', $this->convertRoleToInt($role))
		;
		if ($limit)
		{
			$query->setLimit($limit);
		}

		if ($statuses)
		{
			$query->whereIn('SIGNED', $statuses);
		}

		return $this->extractItemCollectionFromModelCollection($query->fetchCollection());
	}

	public function isSignerExistsByDocumentIdInStatus(int $documentId, array $statuses): bool
	{
		$query = Internal\MemberTable
			::query()
			->addSelect('ID')
			->setLimit(1)
			->where('DOCUMENT_ID', $documentId)
			->where('ROLE', $this->convertRoleToInt(Role::SIGNER))
			->whereIn('SIGNED', $statuses)
		;

		return !empty($query->fetch());
	}

	public function isSignerExistsByDocumentIdNotInStatus(int $documentId, array $statuses): bool
	{
		$query = Internal\MemberTable
			::query()
			->addSelect('ID')
			->setLimit(1)
			->where('DOCUMENT_ID', $documentId)
			->where('ROLE', $this->convertRoleToInt(Role::SIGNER))
			->whereNotIn('SIGNED', $statuses)
		;

		return !empty($query->fetch());
	}

	/**
	 * @param array<int|string, int> $relevance
	 */
	private function getExpressionByRelevance(array $relevance): string
	{
		global $DB;
		$function = static function(string|int $option, int $relevance) use ($DB): string {
			return "WHEN '{$DB->ForSql($option)}' THEN $relevance";
		};
		$conditionString = implode(' ', array_map($function, array_keys($relevance), $relevance));
		$maxRelevance = max($relevance) + 1;

		return "CASE %s {$conditionString} ELSE {$maxRelevance} END";
	}

	public function listByDocumentIdWithRoles(int $documentId, array $memberRoles): Item\MemberCollection
	{
		$roleIds = array_map(fn(string $role) => $this->convertRoleToInt($role), $memberRoles);
		$models = Internal\MemberTable
			::query()
			->addSelect('*')
			->where('DOCUMENT_ID', $documentId)
			->whereIn('ROLE', $roleIds)
			->fetchCollection()
		;

		return $this->extractItemCollectionFromModelCollection($models);
	}

	/**
	 * @param int $documentId
	 * @param list<Role> $memberRoles
	 * @param list<MemberStatus::*> $memberStatuses
	 */
	public function listByDocumentIdWithRolesAndStatuses(int $documentId, array $memberRoles, array $memberStatuses): Item\MemberCollection
	{
		$roleIds = array_map(fn(string $role) => $this->convertRoleToInt($role), $memberRoles);
		$models = Internal\MemberTable
			::query()
			->addSelect('*')
			->where('DOCUMENT_ID', $documentId)
			->whereIn('ROLE', $roleIds)
			->whereIn('SIGNED', $memberStatuses)
			->fetchCollection()
		;

		return $this->extractItemCollectionFromModelCollection($models);
	}

	/**
	 * @param list<MemberStatus::*> $memberStatuses
	 */
	public function listByDocumentIdWithStatuses(int $documentId, array $memberStatuses): Item\MemberCollection
	{
		$models = Internal\MemberTable
			::query()
			->addSelect('*')
			->where('DOCUMENT_ID', $documentId)
			->whereIn('SIGNED', $memberStatuses)
			->fetchCollection()
		;

		return $this->extractItemCollectionFromModelCollection($models);
	}

	public function existsByDocumentIdWithRoleAndStatus(int $documentId, string $role, string $status): bool
	{
		$roleInt = $this->convertRoleToInt($role);

		$model = Internal\MemberTable::query()
			->addSelect('*')
			->where('DOCUMENT_ID', $documentId)
			->where('ROLE', $roleInt)
			->where('SIGNED', $status)
			->setLimit(1)
			->fetchObject()
		;

		return $model !== null;
	}

	public function existsByDocumentIdWithReminderTypeNotEqual(int $documentId, ReminderType $reminderType): bool
	{
		$model = Internal\MemberTable::query()
			->addSelect('*')
			->where('DOCUMENT_ID', $documentId)
			->whereNot('REMINDER_TYPE', $reminderType->toInt())
			->setLimit(1)
			->fetchObject()
		;

		return $model !== null;
	}

	public function updateMembersReminderTypeByRole(int $documentId, string $memberRole, ReminderType $reminderType): Main\Result
	{
		$members = $this->listByDocumentIdWithRole($documentId, $memberRole);
		$ids = $members->getIds();
		if (empty($ids))
		{
			return new Main\Result();
		}

		return Internal\MemberTable::updateMulti($ids, ['REMINDER_TYPE' => $reminderType->toInt()]);
	}

	public function getByDocumentAndEntityType(int $id, string $entityType): ?Item\Member
	{
		$model = Internal\MemberTable::query()
			->addSelect('*')
			->where('DOCUMENT_ID', $id)
			->where('ENTITY_TYPE', $entityType)
			->setLimit(1)
			->fetchObject()
		;

		return $model !== null ? $this->extractItemFromModel($model) : null;
	}

	/**
	 * @param int $documentId
	 *
	 * @return list<int>
	 */
	public function listUserIdsByDocumentId(int $documentId): array
	{
		$flatArray = [];
		$result = Internal\MemberTable::query()
			->setSelect(['ENTITY_ID'])
			->setDistinct()
			->where('DOCUMENT_ID', $documentId)
			->where('ENTITY_TYPE', EntityType::USER)
			->exec()
		;

		while ($row = $result->fetch())
		{
			$flatArray[] = (int)$row['ENTITY_ID'];
		}

		return $flatArray;
	}

	/**
	 * @param int $documentId
	 *
	 * @return array<int>
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function listUserIdsWithEmployeeIdIsNotSetByDocumentId(int $documentId, ?int $representativeId = null): array
	{
		$result = Internal\MemberTable::query()
			->setSelect(['ENTITY_ID', 'ENTITY_TYPE'])
			->setDistinct()
			->where('DOCUMENT_ID', $documentId)
			->whereNull('EMPLOYEE_ID')
			->exec()
		;

		$userIds = [];
		while ($row = $result->fetch())
		{
			$entityId = $row['ENTITY_ID'];
			if ($representativeId > 0 && $row['ENTITY_TYPE'] === EntityType::COMPANY)
			{
				$entityId = $representativeId;
			}

			$userIds[] = (int)$entityId;
		}

		return array_values(
			array_unique($userIds)
		);
	}

	private function getSomeSignedSubQuery(): Query	
	{
		return Internal\MemberTable::query()
			->setCustomBaseTableAlias("sign_internal_member_someone_signed")
			->where('DOCUMENT_ID', new \Bitrix\Main\DB\SqlExpression('%s'))
			->where('ROLE', Role::SIGNER)
			->whereNotIn('SIGNED', [MemberStatus::STOPPABLE_READY, MemberStatus::WAIT, MemberStatus::STOPPED, MemberStatus::REFUSED])
			->setLimit(1)
		;
	}
}

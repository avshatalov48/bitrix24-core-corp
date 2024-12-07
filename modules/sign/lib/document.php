<?php
namespace Bitrix\Sign;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Document\Entity\Dummy;
use Bitrix\Sign\Document\Entity;
use Bitrix\Sign\Document\Member;
use Bitrix\Sign\Integration\CRM\Model\EventData;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\DocumentStatus;

Loc::loadMessages(__FILE__);

/**
 * @deprecated use \Bitrix\Sign\Item\Document and \Bitrix\Sign\Repository\DocumentRepository instead
 */
class Document extends \Bitrix\Sign\Internal\BaseTable
{
	/**
	 * Available keys for meta field.
	 */
	public const META_KEYS = [
		'companyId' => 'companyId',
		'companyTitle' => 'companyTitle',
		'companyRequisites' => 'companyRequisites',
		'initiatorName' => 'initiatorName',
	];

	/**
	 * Available entities classes from Document\Entity.
	 */
	private const AVAILABLE_ENTITY = ['SMART'];

	/**
	 * Internal class.
	 * @var string
	 */
	public static $internalClass = 'DocumentTable';

	/**
	 * Document data.
	 * @var array
	 */
	protected $data;

	/**
	 * Linked entity.
	 * @var Dummy
	 */
	private $entity;

	/**
	 * Document's members.
	 * @var array|null
	 */
	private $members = null;

	/**
	 * Document constructor.
	 * @param array $row Document data.
	 */
	private function __construct(array $row)
	{
		$this->data = $row;
		$entityClass = self::getEntityClass($row['ENTITY_TYPE']);
		if (class_exists($entityClass))
		{
			$this->entity = new $entityClass($row['ENTITY_ID']);
		}
		else
		{
			throw new \Bitrix\Main\SystemException(
				'Class for entity type ' . $row['ENTITY_TYPE'] . ' is not exists.'
			);
		}
	}

	/**
	 * Registers current document to proxy.
	 * @param bool $newBlank Send blank's files (otherwise by default send only blank id).
	 * @return void
	 */
	public function register(bool $newBlank = false): void
	{
		$hash = Proxy::sendCommand('document.register', [
			'data' => $this->data
		]);

		if ($hash)
		{
			if (!$this->setData(['HASH' => $hash]))
			{
				$hash = null;
			}
		}

		// register blank
		if ($hash && $this->data['BLANK_ID'])
		{
			$blank = Blank::getById($this->data['BLANK_ID']);

			if (!$blank)
			{
				Error::getInstance()->addError('NO_SUCH_BLANK', 'no such blank');
				return;
			}

			if ($newBlank)
			{
				$files = $blank->getFiles();
				foreach ($files as $file)
				{
					// file naming deprecated since documentHash added to request params
					$file->setName($this->data['HASH'] . '.' . $file->getExtension());

					/** @var bool $blankRegistered */
					if (!$file->getContent())
					{
						$blankRegistered = false;
					}
					else
					{
						$blankRegistered = Proxy::sendFile(
							'document.registerBlank',
							$file,
							[
								'blankId' => $blank->getId(),
								'documentHash' => $this->data['HASH'],
								'totalFiles' => count($files),
							],
							['timeout' => 120] // ['timeout' => 1]
						);
					}

					if (!$blankRegistered)
					{
						if (Error::getInstance()->isEmpty())
						{
							// TODO async
							Error::getInstance()->addError(
								'PROXY_SEND_ERROR',
								Loc::getMessage('SIGN_CORE_DOCUMENT_ERROR_BLANK_REGISTER')
							);
						}

						$this->rollback();
						break;
					}
				}
			}
			else
			{
				Proxy::sendCommand('document.updateBlank', [
					'documentHash' => $hash,
					'blankId' => $blank->getId()
				]);
			}
		}
	}

	/**
	 * Returns true, if current document still can be changed.
	 * @return bool
	 */
	public function canBeChanged(): bool
	{
		if (!$this->data['HASH'])
		{
			return true;
		}

		if (Storage::instance()->isNewSignEnabled() && $this->data['UID'])
		{
			$document = Container::instance()->getDocumentService()->getByUid($this->data['UID']);

			if (!$document)
			{
				return false;
			}

			return in_array(
				$document->status,
				[DocumentStatus::NEW, DocumentStatus::UPLOADED],
				true
			);
		}

		return Proxy::sendCommand('document.canBeChanged', [
			'hash' => $this->data['HASH']
		]) === true;
	}

	/**
	 * Create document by rows data
	 *
	 * @param array $row
	 *
	 * @return self
	 */
	public static function createByRow(array $row): self
	{
		return new self($row);
	}

	public static function tryCreateByRow(array $row): ?self
	{
		try
		{
			return new self($row);
		}
		catch (SystemException $exception)
		{
			return null;
		}
	}

	/**
	 * Get document third party members
	 * @return Member[]
	 */
	public function getThirdPartyMembers(): array
	{
		$result = [];
		foreach ($this->getMembers() as $member)
		{
			if ($member->isThirdParty())
			{
				$result[] = $member;
			}
		}
		return $result;
	}

	/**
	 * Copies current Document in new blank.
	 * @param bool $sendCommand Send command about copy to Safe.
	 * @return bool
	 */
	public function copyInNewBlank(bool $sendCommand = true): bool
	{
		$currentBlank = $this->getBlank();
		if ($currentBlank)
		{
			$newBlank = $currentBlank->copyBlank();
			if ($newBlank)
			{
				$res = $this->setData([
					'BLANK_ID' => $newBlank->getId()
				]);

				if (!$sendCommand)
				{
					return true;
				}

				if ($res)
				{
					$proxyBlankId = Proxy::sendCommand('document.copyInNewBlank', [
						'hash' => $this->data['HASH'],
						'oldBlankId' => $currentBlank->getId(),
						'newBlankId' => $newBlank->getId()
					]);
					if ($proxyBlankId > 0)
					{
						return true;
					}
					else
					{
						$newBlank->unlink();
						$this->setData([
							'BLANK_ID' => $currentBlank->getId()
						]);
					}
				}
			}
		}

		return false;
	}

	/**
	 * @param Dummy|mixed $entity
	 * @return Document
	 */
	public function setEntity(mixed $entity): self
	{
		$this->entity = $entity;
		return $this;
	}


	/**
	 * Sends current document to proxy.
	 * @return void
	 */
	public function send(): Result
	{
		$data = [];
		$result = new Result();
		$members = $this->getMembers(true);
		if (count($members) < 2)
		{
			return $result->addError(
				new \Bitrix\Main\Error(
				Loc::getMessage('SIGN_CORE_DOCUMENT_ERROR_MEMBERS_NOT_ENOUGH'),
				'MEMBERS_NOT_ENOUGH'
				)
			);
		}

		$data['TITLE'] = $this->entity->getTitle();
		$data['META'] = [
			self::META_KEYS['companyId'] => $this->entity->getCompanyId(),
			self::META_KEYS['companyTitle'] => $this->entity->getCompanyTitle(),
			self::META_KEYS['initiatorName'] => $this->data['META'][self::META_KEYS['initiatorName']] ?? null,
		];

		$proxyResult = Proxy::sendCommand('document.send', [
			'hash' => $this->data['HASH'],
			'data' => [
				'TITLE' => $data['TITLE'],
				'META' => $data['META']
			],
			'members' => $members
		]);

		if ($proxyResult)
		{
			$this->setData($data);

			$eventData = new EventData();
			$eventData
				->setEventType(EventData::TYPE_ON_REGISTER)
				->setDocument($this);

			ServiceLocator::getInstance()->get('sign.service.integration.crm.events')
				->createTimelineEvent($eventData);
		}
		
		return $result;
	}

	/**
	 * Returns true if document is registered.
	 * @return bool
	 */
	public function isRegistered(): bool
	{
		return $this->data['HASH'] !== null;
	}

	/**
	 * Returns document's stage id.
	 * @return int
	 */
	public function getId(): int
	{
		return $this->data['ID'];
	}

	/**
	 * Returns document's hash.
	 * @return string|null
	 */
	public function getHash(): ?string
	{
		return $this->data['HASH'];
	}
	
	/**
	 * Returns document's hash.
	 * @return string|null
	 */
	public function getUid(): ?string
	{
		return $this->data['UID'];
	}

	/**
	 * Returns document's host.
	 * @return string|null
	 */
	public function getHost(): ?string
	{
		return $this->data['HOST'];
	}

	/**
	 * Returns document's sec code.
	 * @return string|null
	 */
	public function getSecCode(): ?string
	{
		return $this->data['SEC_CODE'];
	}


	/**
	 * Returns document's language id.
	 * @return string
	 */
	public function getLanguageId(): string
	{
		return $this->data['LANG_ID'] ?: LANGUAGE_ID;
	}

	/**
	 * Returns document's blank id.
	 * @return int
	 */
	public function getBlankId(): int
	{
		return $this->data['BLANK_ID'];
	}

	/**
	 * Returns document's blank instance.
	 * @return Blank|null
	 */
	public function getBlank(): ?Blank
	{
		$blank = Blank::getById($this->data['BLANK_ID']);
		if ($blank)
		{
			$blank->setDocument($this);
		}

		return $blank;
	}

	/**
	 * Returns document's entity id.
	 * @return int
	 */
	public function getEntityId(): int
	{
		return $this->entity->getId() ?: $this->data['ENTITY_ID'];
	}

	/**
	 * Returns document's entity number.
	 * @return int|string
	 */
	public function getEntityNumber()
	{
		return $this->entity->getNumber() ?: $this->data['ENTITY_ID'];
	}

	/**
	 * Refreshes entity number and returns new value.
	 * @return string|int|null
	 */
	public function refreshEntityNumber()
	{
		return $this->entity->refreshNumber();
	}

	/**
	 * Returns document's title.
	 * @return string
	 */
	public function getTitle(): string
	{
		return $this->entity->getTitle() ?: $this->data['TITLE'];
	}

	/**
	 * Saves new title to Document.
	 *
	 * @param string $title New title.
	 * @return bool
	 */
	public function setTitle(string $title): bool
	{
		return $this->entity->setTitle($title);
	}

	/**
	 * Returns document user created by.
	 * @return int
	 */
	public function getCreatedUserId(): int
	{
		return $this->data['CREATED_BY_ID'];
	}

	/**
	 * Returns document user modified by.
	 * @return int
	 */
	public function getModifiedUserId(): int
	{
		return $this->data['MODIFIED_BY_ID'];
	}

	/**
	 * Returns document create date.
	 * @return \Bitrix\Main\Type\DateTime
	 */
	public function getDateCreate(): \Bitrix\Main\Type\DateTime
	{
		return $this->data['DATE_CREATE'];
	}

	/**
	 * Returns document final sign date.
	 * @return \Bitrix\Main\Type\DateTime
	 */
	public function getDateSign(): ?\Bitrix\Main\Type\DateTime
	{
		return $this->data['DATE_SIGN'];
	}

	/**
	 * Returns document's stage id.
	 * @return string|null
	 */
	public function getStageId(): ?string
	{
		return $this->entity->getStageId();
	}

	public function getInitiatorName(): ?string
	{
		return $this->data['META']['initiatorName'] ?? null;
	}

	/**
	 * Returns entity base company id.
	 * @return int
	 */
	public function getCompanyId(): int
	{
		if ($this->data['META']['companyId'] ?? null)
		{
			return $this->data['META']['companyId'];
		}

		return $this->entity->getCompanyId();
	}

	/**
	 * Returns entity base company id.
	 * @return string
	 */
	public function getCompanyTitle(): ?string
	{
		return $this->entity->getCompanyTitle();
	}

	/**
	 * Sets document meta information.
	 * @param array $meta Meta data.
	 * @return bool
	 */
	public function setMeta(array $meta): bool
	{
		$this->data['META'] = array_merge(
			$this->data['META'] ?: [],
			$meta
		);
		$res = self::update($this->data['ID'], [
			'META' => $this->data['META'],
		]);

		if (!$res->isSuccess())
		{
			Error::getInstance()->addFromResult($res);
			return false;
		}

		return true;
	}

	/**
	 * Returns document meta information.
	 * @return array
	 */
	public function getMeta(): array
	{
		return $this->data['META'];
	}

	/**
	 * Clears assigned to document members.
	 * @return bool
	 */
	public function clearMembers(): bool
	{
		$res = Member::getList([
			'select' => [
				'ID'
			],
			'filter' => [
				'DOCUMENT_ID' => $this->data['ID']
			]
		]);
		while ($row = $res->fetch())
		{
			$resDel = Member::delete($row['ID']);
			if (!$resDel->isSuccess())
			{
				Error::getInstance()->addFromResult($resDel);
				return false;
			}
		}

		return true;
	}

	/**
	 * Collects all contacts and base company id from entity and saves as document members.
	 *
	 * @return Result
	 */
	public function assignMembers(): Result
	{
		// first delete all document's members
		$this->clearMembers();
		$result = new Result();

		// and then add members
		$part = 1;
		$contactIds = array_merge([0 => 0], $this->entity->getContactsIds());// 0 - my company (not contact)
		foreach ($contactIds as $contactId)
		{
			if ($part > 2)
			{
				continue;
			}

			if (!Member::create($this, $contactId, $part++))
			{
				return $result;
			}
		}

		$afterAssignMembersResult = $this->entity->afterAssignMembers($this);

		if (!$afterAssignMembersResult->isSuccess())
		{
			return $afterAssignMembersResult;
		}

		return $result;
	}

	public function actualizeCompanyRequisites(): array
	{
		return $this->entity->actualizeCompanyRequisites($this);
	}

	/**
	 * Returns communications list for member instance.
	 * @param Member $member Member instance.
	 * @return array
	 */
	public function getMemberCommunications(Member $member): array
	{
		return $this->entity->getCommunications($member);
	}

	/**
	 * Returns data value by key.
	 * @param string $key Data key.
	 * @return mixed|null
	 */
	public function getDataValue(string $key)
	{
		return $this->data[$key] ?? null;
	}

	/**
	 * Returns document's members.
	 * @param bool $asArray Returns list as simple array.
	 * @return Member[] | array
	 */
	public function getMembers(bool $asArray = false): array
	{
		if ($asArray)
		{
			return Member::getDocumentMembers($this, $asArray);
		}

		if (empty($this->members))
		{
			$this->members = Member::getDocumentMembers($this, $asArray);
		}

		return $this->members;
	}

	/**
	 * Returns true if all document members signed this doc.
	 * @return bool
	 */
	public function isAllMembersSigned(): bool
	{
		foreach ($this->getMembers() as $member)
		{
			if (!$member->isSigned())
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns true if any document members signed this doc.
	 * @return bool
	 */
	public function isAnyMembersSigned(): bool
	{
		foreach ($this->getMembers() as $member)
		{
			if ($member->isSigned())
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns true if member signed document.
	 * @return bool
	 */
	public function isSignedByMember($memberHash): bool
	{
		foreach ($this->getMembers() as $member)
		{
			if ($member->isSigned() && $member->getHash() === $memberHash)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns document member by hash.
	 * @param string $hash Member hash.
	 * @return Member|null
	 */
	public function getMemberByHash(string $hash): ?Member
	{
		foreach ($this->getMembers() as $memberItem)
		{
			if ($memberItem->getHash() === $hash)
			{
				return $memberItem;
			}
		}

		return null;
	}

	/**
	 * Returns document member by member id.
	 * @param int $memberId Member id.
	 * @return Member|null
	 */
	public function getMemberById(int $memberId): ?Member
	{
		foreach ($this->getMembers() as $memberItem)
		{
			if ($memberItem->getId() === $memberId)
			{
				return $memberItem;
			}
		}

		return null;
	}

	/**
	 * Returns member by part index.
	 * @param int $part Part index.
	 * @return Member|null
	 */
	public function getMemberByPart(int $part): ?Member
	{
		foreach ($this->getMembers() as $member)
		{
			if ($member->getPart() === $part)
			{
				return $member;
			}
		}

		return null;
	}

	/**
	 * Returns member who inits document.
	 * @return Member|null
	 */
	public function getInitiatorMember(): ?Member
	{
		foreach ($this->getMembers() as $member)
		{
			if ($member->isInitiator())
			{
				return $member;
			}
		}

		return null;
	}

	/**
	 * Returns processing status of document.
	 * @return string
	 */
	public function getProcessingStatus(): string
	{
		return $this->data['PROCESSING_STATUS'];
	}

	/**
	 * Returns class name by entity type.
	 * @param string $entityType Entity type.
	 * @return string
	 */
	private static function getEntityClass(string $entityType): string
	{
		return (new Entity\Factory())->getEntityClassNameByType($entityType) ?? '';
	}

	/**
	 * Returns true if specified entityType is correct.
	 * @param string $entityType Entity type id.
	 * @return bool
	 */
	private static function isCorrectEntityType(string $entityType): bool
	{
		return in_array(strtoupper($entityType), self::AVAILABLE_ENTITY);
	}

	/**
	 * Finds document by id and returns its instance.
	 * @param int $id Document id.
	 * @return static|null
	 */
	public static function getById(int $id): ?self
	{
		$res = self::getList([
			'select' => [
				'*'
			],
			'filter' => [
				'ID' => $id
			],
			'limit' => 1
		]);
		if ($row = $res->fetch())
		{
			return new static($row);
		}

		Error::getInstance()->addError(
			'NOT_FOUND',
			Loc::getMessage('SIGN_CORE_DOCUMENT_ERROR_NOT_FOUND')
		);

		return null;
	}

	/**
	 * Finds document by hash and returns its instance.
	 * @param string $hash Document hash.
	 * @return static|null
	 */
	public static function getByHash(string $hash): ?self
	{
		$res = self::getList([
			'select' => [
				'*'
			],
			'filter' => [
				'=HASH' => $hash
			],
			'limit' => 1,
			'order' => [
				'ID' => 'desc'
			]
		]);
		if ($row = $res->fetch())
		{
			return new static($row);
		}

		Error::getInstance()->addError(
			'NOT_FOUND',
			Loc::getMessage('SIGN_CORE_DOCUMENT_ERROR_NOT_FOUND')
		);

		return null;
	}

	/**
	 * Finds document by entity type and id. If not found, creates new document by entity type id.
	 * @param int $blankId Blank id.
	 * @param string $entityType Entity type.
	 * @param int|null $entityId Entity id (if not specified, creates new empty entity).
	 * @return static|null
	 */
	public static function createByBlank(int $blankId, string $entityType, ?int $entityId = null): ?self
	{
		if (!\Bitrix\Sign\Restriction::isNewDocAllowed())
		{
			Error::getInstance()->addError(
				'DOCS_LIMIT_EXCEEDED',
				Loc::getMessage('SIGN_CORE_DOCUMENT_ERROR_DOCS_LIMIT_EXCEEDED')
			);
			return null;
		}

		if (self::isCorrectEntityType($entityType))
		{
			if (!$entityId)
			{
				/** @var Document\Entity\Dummy $entityClass */
				$entityClass = self::getEntityClass($entityType);
				$entityId = $entityClass::create();
			}
			else
			{
				$res = self::getList([
					'select' => [
						'*'
					],
					'filter' => [
						'=ENTITY_TYPE' => $entityType,
						'=ENTITY_ID' => $entityId
					],
					'limit' => 1
				]);
				if ($row = $res->fetch())
				{
					if ($row['BLANK_ID'] !== $blankId)
					{
						self::update($row['ID'], [
							'BLANK_ID' => $blankId
						])->isSuccess();
					}
					return new static($row);
				}
			}

			$data = [
				'BLANK_ID' => $blankId,
				'ENTITY_TYPE' => $entityType,
				'ENTITY_ID' => $entityId
			];

			$res = self::add($data);
			if ($res->isSuccess())
			{
				$document = self::getById($res->getId());

				if ($document ?? false)
				{
					Error::getInstance()->clear();
					ServiceLocator::getInstance()->get('sign.service.integration.crm.events')
						->createTimelineEvent(
							(new EventData())->setEventType(EventData::TYPE_ON_CREATE)
							->setDocument($document)
						);
				}

				return $document;
			}
			else
			{
				Error::getInstance()->addFromResult($res);
			}
		}
		else
		{
			Error::getInstance()->addError(
				'INCORRECT_ENTITY_TYPE',
				Loc::getMessage('SIGN_CORE_DOCUMENT_ERROR_INCORRECT_ENTITY_TYPE')
			);
		}

		return null;
	}

	/**
	 * Finds document by entity type and id.
	 * @param string $entityType Entity type.
	 * @param int|null $entityId Entity id.
	 * @return static|null
	 * @deprecated
	 * @see DocumentRepository::getByEntityIdAndType()
	 */
	public static function resolveByEntity(string $entityType, ?int $entityId): ?self
	{
		if (!$entityId)
		{
			return null;
		}
		$res = self::getList([
			'select' => [
				'*'
			],
			'filter' => [
				'=ENTITY_TYPE' => $entityType,
				'=ENTITY_ID' => $entityId
			],
			'limit' => 1
		]);
		if ($row = $res->fetch())
		{
			return new static($row);
		}

		return null;
	}

	/**
	 * Returns document's layout.
	 * @return array
	 */
	public function getLayout(): array
	{
		$layout = Proxy::sendCommand('document.getLayout', [
			'hash' => $this->data['HASH']
		]);

		if ($layout)
		{
			foreach ($layout['layout'] as &$item)
			{
				$item['path'] = Proxy::getCommandUrl('document.getPreviewByHash', [
					'data' => [
						'fileHash' => $item['hash']
					]
				]);
			}
			unset($item);

			return $layout;
		}

		return [];
	}

	/**
	 * Set result file to the Doc.
	 * @param File $file File instance.
	 * @return bool
	 */
	public function setResultFile(File $file): bool
	{
		if ($this->data['RESULT_FILE_ID'])
		{
			File::delete($this->data['RESULT_FILE_ID']);
		}

		return $this->setData([
			'RESULT_FILE_ID' => $file->save()
		]);
	}

	/**
	 * Returns result file if exists.
	 * @return File|null
	 */
	public function getResultFile(): ?File
	{
		if ($this->data['RESULT_FILE_ID'])
		{
			return new File((int) $this->data['RESULT_FILE_ID']);
		}

		return null;
	}

	/**
	 * Deletes current Document.
	 * @return bool
	 */
	public function unlink(): bool
	{
		return self::delete($this->data['ID'])->isSuccess();
	}

	public function rollback(): bool
	{
		if ($blank = $this->getBlank())
		{
			// TODO delete entity
			return $this->unlink() && $blank->unlink();
		}

		return false;
	}
}
